<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
// Do NOT include the controller manually yet. Let's see if it's already loaded or autoloaded.

$database = new Database();
$db = $database->getConnection();

echo "Checking if ReportController is defined...<br>";
if (class_exists('ReportController')) {
    echo "ReportController is already defined!<br>";
    $reflector = new ReflectionClass('ReportController');
    echo "Defined in: " . $reflector->getFileName() . "<br>";
} else {
    echo "ReportController is NOT defined. Attempting to include...<br>";
    require_once __DIR__ . '/controllers/ReportController.php';
    if (class_exists('ReportController')) {
        echo "Loaded ReportController from: " . __DIR__ . "/controllers/ReportController.php<br>";
        $reflector = new ReflectionClass('ReportController');
        echo "Defined in: " . $reflector->getFileName() . "<br>";

        $methods = get_class_methods('ReportController');
        echo "Methods: <pre>" . print_r($methods, true) . "</pre>";
    } else {
        echo "FAILED to load ReportController even after require!<br>";
    }
}
