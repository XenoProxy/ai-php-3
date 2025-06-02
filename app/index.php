<?php

// Database file for SQLite
$dbFile = 'sales_data.db';

try {
    // Connect to SQLite
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the 'orders' table already exists
    $checkTableStmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='orders'");
    $checkTableStmt->execute();
    $tableExists = $checkTableStmt->fetchColumn();

    // Create table and insert data only if the table doesn't exist
    if (!$tableExists) {
        // SQL script to create the 'orders' table
        $createTableSql = "
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer TEXT,
                amount REAL,
                order_date DATE
            );
        ";
        $pdo->exec($createTableSql);

        // SQL script to insert the provided sample data
        $insertDataSql = "
            INSERT INTO orders (customer, amount, order_date) VALUES
            ('Alice', 5000, '2024-03-01'),
            ('Bob', 8000, '2024-03-05'),
            ('Alice', 3000, '2024-03-15'),
            ('Charlie', 7000, '2024-02-20'),
            ('Alice', 10000, '2024-02-28'),
            ('Bob', 4000, '2024-02-10'),
            ('Charlie', 9000, '2024-03-22'),
            ('Alice', 2000, '2024-03-30');
        ";
        $pdo->exec($insertDataSql);

        echo "<p>Database table 'orders' created and data initialized.</p>\n";
    } else {
        echo "<p>Database table 'orders' already exists. Skipping data initialization.</p>\n";
    }

    echo "<h2>Sales Data Analysis</h2>\n";

    // Task 1: Total sales for March 2024
    $stmtTotalMarch = $pdo->prepare("
        SELECT SUM(amount) AS total_sales_march
        FROM orders
        WHERE STRFTIME('%Y-%m', order_date) = '2024-03'
    ");
    $stmtTotalMarch->execute();
    $resultTotalMarch = $stmtTotalMarch->fetch(PDO::FETCH_ASSOC);
    $totalSalesMarch = $resultTotalMarch['total_sales_march'] ?? 0;

    echo "<h3>Total Sales for March 2024:</h3>\n";
    echo "<ul><li><strong>Total:</strong> " . number_format($totalSalesMarch, 2) . "</li></ul>\n\n";

    // Task 2: Top-spending customer
    $stmtTopCustomer = $pdo->prepare("
        SELECT customer, SUM(amount) AS total_spent
        FROM orders
        GROUP BY customer
        ORDER BY total_spent DESC
        LIMIT 1
    ");
    $stmtTopCustomer->execute();
    $resultTopCustomer = $stmtTopCustomer->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Top-Spending Customer:</h3>\n";
    if ($resultTopCustomer) {
        $topCustomer = htmlspecialchars($resultTopCustomer['customer']);
        $topSpending = number_format($resultTopCustomer['total_spent'], 2);
        echo "<ul><li><strong>Customer:</strong> " . $topCustomer . "</li><li><strong>Total Spent:</strong> " . $topSpending . "</li></ul>\n\n";
    } else {
        echo "<ul><li>No top-spending customer found.</li></ul>\n\n";
    }

    // Task 3: Average order value (last three months)
    $threeMonthsAgo = date('Y-m-d', strtotime('-3 months', strtotime('2024-03-30')));
    $stmtAvgOrderValue = $pdo->prepare("
        SELECT AVG(amount) AS average_order_value
        FROM orders
        WHERE order_date >= :three_months_ago
    ");
    $stmtAvgOrderValue->bindParam(':three_months_ago', $threeMonthsAgo);
    $stmtAvgOrderValue->execute();
    $resultAvgOrderValue = $stmtAvgOrderValue->fetch(PDO::FETCH_ASSOC);
    $averageOrderValue = number_format($resultAvgOrderValue['average_order_value'] ?? 0, 2);

    echo "<h3>Average Order Value (Last Three Months):</h3>\n";
    echo "<ul><li><strong>Starting From:</strong> " . $threeMonthsAgo . "</li><li><strong>Average Value:</strong> " . $averageOrderValue . "</li></ul>\n\n";

    // Close connection
    $pdo = null;

} catch (PDOException $e) {
    die("<h2>Error</h2><p>Connection/SQL error: " . htmlspecialchars($e->getMessage()) . "</p>");
}
?>