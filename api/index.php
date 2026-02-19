<?php

/**
 * Farm Management API Router
 * Main entry point for all API requests
 * 
 * Deploy to InfinityFree htdocs/api/index.php
 */

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once __DIR__ . '/config/database.php';

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the request path (remove query string, /api prefix, and index.php)
$path = parse_url($requestUri, PHP_URL_PATH);
$path = preg_replace('/^.*\/api/', '', $path);
$path = preg_replace('/^\/index\.php/', '', $path); // Remove index.php if present
$path = trim($path, '/');
$segments = $path ? explode('/', $path) : [];

// Filter out empty segments and 'index.php'
$segments = array_values(array_filter($segments, function ($s) {
    return $s !== '' && $s !== 'index.php';
}));

// Get query parameters
$queryParams = $_GET;

// Helper function to get resource ID from segments
function getResourceId($segments, $index)
{
    return isset($segments[$index]) ? $segments[$index] : null;
}

// Helper function to send JSON response
function sendResponse($data)
{
    echo json_encode($data);
    exit();
}

/**
 * Send raw JSON response (bypasses any potential wrapper)
 */
function sendRawResponse($data)
{
    echo json_encode($data);
    exit();
}

// Global exception handler to prevent 500 errors from becoming HTML
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error',
        'error' => $e->getMessage()
    ]);
    exit();
});

// Route the request
try {
    $resource = $segments[0] ?? '';
    $id = getResourceId($segments, 1);
    $subResource = $segments[2] ?? null;
    $subId = getResourceId($segments, 3);

    switch ($resource) {
        // ===== AUTH ROUTES =====
        case 'auth':
            require_once __DIR__ . '/controllers/AuthController.php';
            $controller = new AuthController($db);

            switch ($id) {
                case 'login':
                    sendResponse($controller->login());
                    break;
                case 'register':
                    sendResponse($controller->register());
                    break;
                case 'me':
                    sendRawResponse($controller->me());
                    break;
                case 'logout':
                    sendResponse($controller->logout());
                    break;
                default:
                    http_response_code(404);
                    sendResponse(['success' => false, 'message' => 'Auth route not found']);
            }
            break;

        // ===== USERS ROUTES =====
        case 'users':
            require_once __DIR__ . '/controllers/UserController.php';
            $controller = new UserController($db);

            if ($method === 'GET' && !$id) {
                $response = $controller->index();
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                $response = $controller->store();
                sendRawResponse($response['data'] ?? $response);
            } elseif ($method === 'PUT' && $id) {
                $response = $controller->update($id);
                sendRawResponse($response['data'] ?? $response);
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== FARMS ROUTES =====
        case 'farms':
            require_once __DIR__ . '/controllers/FarmController.php';
            $controller = new FarmController($db);

            if ($method === 'GET' && !$id) {
                $userId = $queryParams['user_id'] ?? null;
                $response = $controller->index($userId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                // Check for nested resources
                if ($subResource === 'fields') {
                    require_once __DIR__ . '/controllers/FieldController.php';
                    $fieldController = new FieldController($db);
                    $response = $fieldController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'crops') {
                    require_once __DIR__ . '/controllers/CropController.php';
                    $cropController = new CropController($db);
                    $response = $cropController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'activities') {
                    require_once __DIR__ . '/controllers/ActivityController.php';
                    $activityController = new ActivityController($db);
                    $response = $activityController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'harvests') {
                    require_once __DIR__ . '/controllers/HarvestController.php';
                    $harvestController = new HarvestController($db);
                    $response = $harvestController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'infrastructure') {
                    require_once __DIR__ . '/controllers/InfrastructureController.php';
                    $infraController = new InfrastructureController($db);
                    $response = $infraController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'team') {
                    require_once __DIR__ . '/controllers/TeamController.php';
                    $teamController = new TeamController($db);
                    $response = $teamController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'cost-settings') {
                    require_once __DIR__ . '/controllers/CostSettingController.php';
                    $costController = new CostSettingController($db);
                    $response = $costController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'weather') {
                    require_once __DIR__ . '/controllers/WeatherController.php';
                    $weatherController = new WeatherController($db);
                    if ($subId === 'forecast') {
                        sendRawResponse($weatherController->forecast($id));
                    } else {
                        sendRawResponse($weatherController->current($id));
                    }
                } elseif ($subResource === 'inputs') {
                    require_once __DIR__ . '/controllers/InputController.php';
                    $inputController = new InputController($db);
                    $response = $inputController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'contracts') {
                    require_once __DIR__ . '/controllers/ContractController.php';
                    $contractController = new ContractController($db);
                    $response = $contractController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'documents') {
                    require_once __DIR__ . '/controllers/DocumentController.php';
                    $docController = new DocumentController($db);
                    $response = $docController->index($id);
                    sendRawResponse($response['data'] ?? []);
                } else {
                    $response = $controller->show($id);
                    sendRawResponse($response['data'] ?? new stdClass());
                }
            } elseif ($method === 'POST') {
                if ($subResource === 'fields') {
                    require_once __DIR__ . '/controllers/FieldController.php';
                    $fieldController = new FieldController($db);
                    sendResponse($fieldController->store($id));
                } elseif ($subResource === 'crops') {
                    require_once __DIR__ . '/controllers/CropController.php';
                    $cropController = new CropController($db);
                    sendResponse($cropController->store($id));
                } elseif ($subResource === 'infrastructure') {
                    require_once __DIR__ . '/controllers/InfrastructureController.php';
                    $infraController = new InfrastructureController($db);
                    sendResponse($infraController->store($id));
                } elseif ($subResource === 'activities') {
                    require_once __DIR__ . '/controllers/ActivityController.php';
                    $activityController = new ActivityController($db);
                    sendResponse($activityController->store($id));
                } elseif ($subResource === 'team') {
                    require_once __DIR__ . '/controllers/TeamController.php';
                    $teamController = new TeamController($db);
                    sendResponse($teamController->store($id));
                } elseif ($subResource === 'inputs') {
                    require_once __DIR__ . '/controllers/InputController.php';
                    $inputController = new InputController($db);
                    sendResponse($inputController->store($id));
                } else {
                    sendResponse($controller->store());
                }
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== FIELDS ROUTES =====
        case 'fields':
            require_once __DIR__ . '/controllers/FieldController.php';
            $controller = new FieldController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                // Check for nested resources: /fields/{id}/crops
                if ($subResource === 'crops') {
                    require_once __DIR__ . '/controllers/CropController.php';
                    $cropController = new CropController($db);
                    if ($method === 'POST') {
                        sendResponse($cropController->store(null, $id));
                    } else {
                        $response = $cropController->byField($id);
                        sendRawResponse($response['data'] ?? []);
                    }
                } else {
                    $response = $controller->show($id);
                    sendRawResponse($response['data'] ?? new stdClass());
                }
            } elseif ($method === 'POST' && $id && $subResource === 'crops') {
                // POST /fields/{id}/crops
                require_once __DIR__ . '/controllers/CropController.php';
                $cropController = new CropController($db);
                sendResponse($cropController->store(null, $id));
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== CROPS ROUTES =====
        case 'crops':
            require_once __DIR__ . '/controllers/CropController.php';
            $controller = new CropController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'all') {
                // GET /crops/all - return all crops
                $response = $controller->index(null);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'active') {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->active($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                // Check for nested resources: /crops/{id}/activities, /crops/{id}/harvests
                if ($subResource === 'activities') {
                    require_once __DIR__ . '/controllers/ActivityController.php';
                    $activityController = new ActivityController($db);
                    $response = $activityController->byCrop($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'harvests') {
                    require_once __DIR__ . '/controllers/HarvestController.php';
                    $harvestController = new HarvestController($db);
                    $response = $harvestController->byCrop($id);
                    sendRawResponse($response['data'] ?? []);
                } elseif ($subResource === 'timeline') {
                    // Return empty timeline for now (placeholder)
                    sendRawResponse([]);
                } else {
                    $response = $controller->show($id);
                    sendRawResponse($response['data'] ?? new stdClass());
                }
            } elseif ($method === 'POST' && $id && $subResource === 'activities') {
                // POST /crops/{id}/activities
                require_once __DIR__ . '/controllers/ActivityController.php';
                $activityController = new ActivityController($db);
                sendResponse($activityController->store(null, $id, 'crop'));
            } elseif ($method === 'POST' && $id && $subResource === 'harvests') {
                // POST /crops/{id}/harvests
                require_once __DIR__ . '/controllers/HarvestController.php';
                $harvestController = new HarvestController($db);
                sendResponse($harvestController->store($id));
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== ACTIVITIES ROUTES =====
        case 'activities':
            require_once __DIR__ . '/controllers/ActivityController.php';
            $controller = new ActivityController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'all') {
                // GET /activities/all - return all activities
                $response = $controller->index(null);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST' && $id === 'bulk-upload' && $subResource) {
                // POST /activities/bulk-upload/{farmId}
                // Placeholder for bulk upload
                http_response_code(501);
                sendResponse(['success' => false, 'message' => 'Bulk upload not implemented']);
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== HARVESTS ROUTES =====
        case 'harvests':
            require_once __DIR__ . '/controllers/HarvestController.php';
            $controller = new HarvestController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'all') {
                // GET /harvests/all - return all harvests
                $response = $controller->index(null);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== CONTRACTS ROUTES =====
        case 'contracts':
            require_once __DIR__ . '/controllers/ContractController.php';
            $controller = new ContractController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'all') {
                $response = $controller->index(null);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== INPUTS ROUTES =====
        case 'inputs':
            require_once __DIR__ . '/controllers/InputController.php';
            $controller = new InputController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'all') {
                $response = $controller->index(null);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== COST SETTINGS ROUTES =====
        case 'cost-settings':
            require_once __DIR__ . '/controllers/CostSettingController.php';
            $controller = new CostSettingController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'defaults') {
                $response = $controller->defaults();
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== DOCUMENTS ROUTES =====
        case 'documents':
            require_once __DIR__ . '/controllers/DocumentController.php';
            $controller = new DocumentController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== TEAM ROUTES =====
        case 'team':
            require_once __DIR__ . '/controllers/TeamController.php';
            $controller = new TeamController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                $response = $controller->store();
                sendRawResponse($response['data'] ?? $response);
            } elseif ($method === 'PUT' && $id) {
                $response = $controller->update($id);
                sendRawResponse($response['data'] ?? $response);
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== WORK ORDER ROUTES =====
        case 'work-orders':
            require_once __DIR__ . '/controllers/WorkOrderController.php';
            $controller = new WorkOrderController($db);

            switch ($method) {
                case 'GET':
                    if (isset($queryParams['farm_id'])) {
                        $response = $controller->index($queryParams['farm_id']);
                        sendResponse($response);
                    } else {
                        http_response_code(400);
                        sendResponse(['success' => false, 'message' => 'Farm ID required']);
                    }
                    break;
                case 'POST':
                    $response = $controller->store();
                    sendResponse($response);
                    break;
                case 'PUT':
                case 'PATCH':
                    if ($id) {
                        $response = $controller->update($id);
                        sendResponse($response);
                    }
                    break;
                case 'DELETE':
                    if ($id) {
                        $response = $controller->delete($id);
                        sendResponse($response);
                    }
                    break;
            }
            break;

        // ===== CROP DEFINITIONS ROUTES =====
        case 'crop-definitions':
            require_once __DIR__ . '/controllers/CropDefinitionController.php';
            $controller = new CropDefinitionController($db);

            if ($method === 'GET' && !$id) {
                $response = $controller->index();
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== INFRASTRUCTURE ROUTES =====
        case 'infrastructure':
            require_once __DIR__ . '/controllers/InfrastructureController.php';
            $controller = new InfrastructureController($db);

            if ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'all') {
                $response = $controller->index(null);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id === 'farm' && $subResource) {
                // GET /infrastructure/farm/{farmId}
                $response = $controller->index($subResource);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                // Check for nested activities
                if ($subResource === 'activities') {
                    require_once __DIR__ . '/controllers/ActivityController.php';
                    $activityController = new ActivityController($db);
                    if ($method === 'POST') {
                        sendResponse($activityController->store(null, $id, 'infrastructure'));
                    } else {
                        $response = $activityController->byInfrastructure($id);
                        sendRawResponse($response['data'] ?? []);
                    }
                } else {
                    $response = $controller->show($id);
                    sendRawResponse($response['data'] ?? new stdClass());
                }
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== INFRASTRUCTURE DEFINITIONS ROUTES =====
        case 'infrastructure-definitions':
            require_once __DIR__ . '/controllers/InfrastructureDefinitionController.php';
            $controller = new InfrastructureDefinitionController($db);

            if ($method === 'GET' && !$id) {
                $response = $controller->index();
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'GET' && $id) {
                $response = $controller->show($id);
                sendRawResponse($response['data'] ?? new stdClass());
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            } elseif ($method === 'PUT' && $id) {
                sendResponse($controller->update($id));
            } elseif ($method === 'DELETE' && $id) {
                sendResponse($controller->destroy($id));
            }
            break;

        // ===== REPORTS ROUTES =====
        case 'reports':
            require_once __DIR__ . '/controllers/ReportController.php';
            $controller = new ReportController($db);

            if ($method === 'GET' && $id) {
                // GET /reports/{reportType}?farm_id=xxx&format=json|csv|html_print
                $response = $controller->produce($id);

                // For HTML/CSV formats, output is handled internally (exits script)
                // Only send JSON response if format is json or not specified
                if (!isset($_GET['format']) || $_GET['format'] === 'json') {
                    sendResponse($response);
                }
            } else {
                http_response_code(400);
                sendResponse(['success' => false, 'message' => 'Report type required']);
            }
            break;

        // ===== WEATHER ROUTES =====
        case 'weather':
            require_once __DIR__ . '/controllers/WeatherController.php';
            $controller = new WeatherController($db);
            if ($method === 'GET' && $id === 'current') {
                $farmId = $queryParams['farm_id'] ?? $subResource;
                sendRawResponse($controller->current($farmId));
            } elseif ($method === 'GET' && $id === 'forecast') {
                $farmId = $queryParams['farm_id'] ?? $subResource;
                sendRawResponse($controller->forecast($farmId));
            } elseif ($method === 'GET' && !$id) {
                $farmId = $queryParams['farm_id'] ?? null;
                $response = $controller->index($farmId);
                sendRawResponse($response['data'] ?? []);
            } elseif ($method === 'POST') {
                sendResponse($controller->store());
            }
            break;

        // ===== PLANTING SEASON ROUTES =====
        case 'planting-seasons':
            require_once __DIR__ . '/controllers/PlantingSeasonController.php';
            $controller = new PlantingSeasonController($db);

            if ($method === 'GET' && $id === 'regions') {
                sendRawResponse($controller->getRegions());
            } elseif ($method === 'GET' && $id === 'crops') {
                sendRawResponse($controller->getCrops());
            } elseif ($method === 'GET' && $id === 'zones') {
                sendRawResponse($controller->getZones());
            } elseif ($method === 'GET' && $id === 'calendars') {
                sendRawResponse($controller->getCalendars($queryParams['region_id'] ?? null));
            } elseif ($method === 'GET' && $id === 'analyze') {
                sendRawResponse($controller->analyzeOnset());
            }
            break;

        // ===== REPORTS ROUTES =====
        case 'reports':
            require_once __DIR__ . '/controllers/ReportGenerator.php';
            $controller = new ReportGenerator($db);
            $farmId = $queryParams['farm_id'] ?? null;

            // 1. New Report Types (Financials, Inventory, Activities, + Gap Closure Types)
            if ($method === 'GET' && in_array($id, ['financials', 'inventory', 'activities', 'growth', 'crop-budget', 'activity-log', 'risk'])) {
                $response = $controller->produce($id);
                // If produce() returns data (JSON mode), send it. If it exited (CSV/Print), this won't be reached.
                if ($response) {
                    sendResponse($response);
                }
            }
            // 2. Legacy/Specific Report Endpoints
            else {
                switch ($id) {
                    case 'production-cost':
                        // GET /reports/production-cost/{cropId}
                        if ($subResource) {
                            $response = $controller->productionCost($subResource);
                            sendRawResponse($response['data'] ?? []);
                        } else {
                            // Fallback if needed
                            sendRawResponse([]);
                        }
                        break;
                    case 'costs':
                        $response = $controller->costs($farmId);
                        sendRawResponse($response['data'] ?? []);
                        break;
                    case 'timeline':
                        $days = $queryParams['days'] ?? 30;
                        $response = $controller->timeline($farmId, $days);
                        sendRawResponse($response['data'] ?? []);
                        break;
                    case 'dashboard':
                    case 'production':
                    default:
                        // Return empty for now to prevent 404/500 combo on legacy calls
                        sendRawResponse([]);
                        break;
                }
            }
            break;

        // ===== REPORTS ROUTES =====
        case 'reports':
            require_once __DIR__ . '/controllers/ReportController.php';
            $controller = new ReportController($db);

            // GET /api/reports/{type}?farm_id=X&format=json|csv|html_print
            if ($method === 'GET') {
                $reportType = $id ?? 'financials'; // Default to financials if no type specified
                $farmId = $queryParams['farm_id'] ?? $queryParams['farmId'] ?? null;
                $format = $queryParams['format'] ?? 'json';

                if (!$farmId) {
                    http_response_code(400);
                    sendResponse(['success' => false, 'message' => 'farm_id parameter is required']);
                }

                // Map URL-friendly names to internal types
                $typeMap = [
                    'financial' => 'financials',
                    'crop-budget' => 'crop-budget',
                    'growth-capital' => 'growth',
                    'operations' => 'activities',
                    'activity-log' => 'activity-log',
                    'compliance' => 'activity-log',
                    'risk' => 'risk',
                    'inventory' => 'inventory',
                    'machinery' => 'machinery',
                    'soil-health' => 'soil-health',
                    'weather' => 'weather',
                    'farm-summary' => 'farm-summary',
                    'carbon' => 'carbon',
                    'water' => 'water'
                ];

                $mappedType = $typeMap[$reportType] ?? $reportType;

                // Set farm_id and format in $_GET for controller
                $_GET['farm_id'] = $farmId;
                $_GET['format'] = $format;

                $result = $controller->produce($mappedType);

                // Only send response if format is json (html_print/csv will echo directly)
                if ($format === 'json' || isset($result['success'])) {
                    sendResponse($result);
                }
            } else {
                http_response_code(405);
                sendResponse(['success' => false, 'message' => 'Method not allowed']);
            }
            break;

        // ===== EXPORT ROUTES =====
        case 'export':
            require_once __DIR__ . '/controllers/ExportController.php';
            $controller = new ExportController($db);
            $farmId = $queryParams['farm_id'] ?? null;

            if ($id === 'all') {
                sendResponse($controller->exportAll($farmId));
            } elseif ($subResource === 'csv') {
                sendResponse($controller->exportCsv($id, $farmId));
            } else {
                sendResponse($controller->exportJson($id, $farmId));
            }
            break;

        // ===== SYSTEM ROUTES =====
        case 'system':
            require_once __DIR__ . '/controllers/SystemController.php';
            $controller = new SystemController($db);

            switch ($id) {
                case 'messages':
                    $response = $controller->messages();
                    sendRawResponse($response['data'] ?? []);
                    break;
                case 'health':
                    $response = $controller->health();
                    unset($response['success']);
                    sendRawResponse($response);
                    break;
                case 'info':
                    $response = $controller->info();
                    unset($response['success']);
                    sendRawResponse($response);
                    break;
                default:
                    http_response_code(404);
                    sendResponse(['success' => false, 'message' => 'System route not found']);
            }
            break;



        // ===== DEFAULT =====
        case '':
            sendResponse([
                'success' => true,
                'message' => 'Farm Management API',
                'version' => '2.0.0',
                'endpoints' => [
                    'auth' => '/api/auth',
                    'users' => '/api/users',
                    'farms' => '/api/farms',
                    'fields' => '/api/fields',
                    'crops' => '/api/crops',
                    'activities' => '/api/activities',
                    'harvests' => '/api/harvests',
                    'infrastructure' => '/api/infrastructure',
                    'contracts' => '/api/contracts',
                    'inputs' => '/api/inputs',
                    'documents' => '/api/documents',
                    'team' => '/api/team',
                    'reports' => '/api/reports',
                    'weather' => '/api/weather',
                    'system' => '/api/system'
                ]
            ]);
            break;

        default:
            http_response_code(404);
            sendResponse(['success' => false, 'message' => 'Endpoint not found: ' . $resource]);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    sendResponse([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage() // Remove in production
    ]);
}
