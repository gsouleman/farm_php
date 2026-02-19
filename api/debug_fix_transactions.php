<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Try to include database configuration with case sensitivity handling
$configFile = __DIR__ . '/config/Database.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/config/database.php';
}

if (!file_exists($configFile)) {
    die("Configuration file not found");
}

require_once $configFile;
require_once __DIR__ . '/models/Activity.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $activity = new Activity($db);

    // Initial message
    $message = "";

    // Handle updates
    if (isset($_POST['id']) && isset($_POST['type'])) {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $data = ['transaction_type' => $type];

        // Manual SQL because Activity may be tricky
        $sql = "UPDATE activities SET transaction_type = :type WHERE id = :id";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([':type' => $type, ':id' => $id]);

        if ($result) {
            $message = "<div style='color:green; padding:10px; border:1px solid green; margin-bottom:20px;'>Successfully UPDATED Activity #$id to '$type'</div>";
        } else {
            $message = "<div style='color:red; padding:10px; border:1px solid red; margin-bottom:20px;'>Failed to update Activity #$id</div>";
        }
    }

    // List latest 50 activities
    $sql = "SELECT * FROM activities ORDER BY activity_date DESC LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Activity Transaction Fixer</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .income {
            color: green;
            font-weight: bold;
        }

        .expense {
            color: red;
            font-weight: bold;
        }

        button {
            cursor: pointer;
            padding: 5px 10px;
        }

        .btn-income {
            background: #e6fffa;
            border: 1px solid green;
            color: green;
        }

        .btn-expense {
            background: #fff5f5;
            border: 1px solid red;
            color: red;
        }
    </style>
</head>

<body>
    <h1>Activity Transaction Fixer</h1>
    <p>Use this tool to manually set the Transaction Type for activities that are stubborn.</p>

    <?php echo $message; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Operation</th>
                <th>Description</th>
                <th>Currently</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activities as $act): ?>
                <tr>
                    <td><?php echo $act['id']; ?></td>
                    <td><?php echo $act['activity_date']; ?></td>
                    <td><?php echo $act['activity_type']; ?></td>
                    <td><?php echo $act['description']; ?></td>
                    <td class="<?php echo $act['transaction_type'] == 'income' ? 'income' : 'expense'; ?>">
                        <?php echo strtoupper($act['transaction_type'] ?: 'UNKNOWN'); ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $act['id']; ?>">
                            <input type="hidden" name="type" value="income">
                            <button type="submit" class="btn-income">Set to INCOME</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $act['id']; ?>">
                            <input type="hidden" name="type" value="expense">
                            <button type="submit" class="btn-expense">Set to EXPENSE</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>