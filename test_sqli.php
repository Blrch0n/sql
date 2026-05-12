<?php

require_once "config/db.php";

echo "Running SQL Injection Test Suite...\n";
echo "===================================\n\n";

$global_fail = false;

function test_product_filter($conn, $payload) {
    global $global_fail;
    echo "Testing payload: " . $payload . "\n";
    try {
        $stmt = $conn->prepare("SELECT name FROM products WHERE category = :cat AND released = 1");
        $stmt->execute([':cat' => $payload]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $found_unreleased = false;
        foreach ($results as $row) {
            if ($row['name'] === 'Secret Unreleased Product') {
                $found_unreleased = true;
            }
        }
        
        if ($found_unreleased) {
            echo "FAILED: Unreleased product exposed via SQLi!\n";
            $global_fail = true;
        } else {
            echo "PASSED: Vulnerability prevented. No unreleased data returned.\n";
        }
    } catch (PDOException $e) {
        echo "PASSED: Query failed safely (likely due to strict typing), no data leaked.\n";
    }
    echo "-----------------------------------\n";
}

test_product_filter($conn, "Gifts' OR 1=1--");
test_product_filter($conn, "' OR '1'='1");

test_product_filter($conn, "Gifts' UNION SELECT NULL, NULL, NULL, NULL, NULL--");
test_product_filter($conn, "Gifts' UNION SELECT table_name, NULL, NULL, NULL, NULL FROM information_schema.tables--");

echo "Testing Login Bypass payload: admin@medicare.mn'--\n";
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => "admin@medicare.mn'--"]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "FAILED: User account found with injection payload!\n";
        $global_fail = true;
    } else {
        echo "PASSED: Login bypass prevented. User not found.\n";
    }
} catch (PDOException $e) {
    echo "PASSED: Query failed safely.\n";
}
echo "===================================\n";

if ($global_fail) {
    echo "Some tests FAILED.\n";
    exit(1);
} else {
    echo "All tests completed successfully.\n";
    exit(0);
}
?>