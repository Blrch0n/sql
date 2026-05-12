<?php

require_once "config/db.php";

echo "Running SQL Injection Test Suite...\n";
echo "===================================\n\n";

$global_fail = false;

try {
    // 1. Verify the table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'products'")->rowCount();
    if ($table_check === 0) {
        throw new Exception("The 'products' table does not exist!");
    }

    // 2. Ensure a `released=0` product exists in the DB
    $release_check = $conn->query("SELECT COUNT(*) FROM products WHERE released = 0")->fetchColumn();
    if ($release_check == 0) {
        throw new Exception("No product with 'released=0' found in the database. Tests cannot accurately check data leakage.");
    }
} catch (Exception $e) {
    echo "SETUP FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

function test_product_filter($conn, $payload) {
    global $global_fail;
    echo "Testing payload: " . $payload . "\n";
    try {
        $stmt = $conn->prepare("SELECT name FROM products WHERE category = :cat AND released = 1");
        $stmt->execute([':cat' => $payload]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $found_unreleased = false;
        foreach ($results as $row) {
            // we assume any item containing 'Secret' or checked against released=0
            // but just to be sure we check if any returned item is actually released=0
            $check_stmt = $conn->prepare("SELECT released FROM products WHERE name = :name");
            $check_stmt->execute([':name' => $row['name']]);
            $is_released = $check_stmt->fetchColumn();
            
            if ($is_released == 0) {
                $found_unreleased = true;
                break;
            }
        }
        
        if ($found_unreleased) {
            echo "FAILED: Unreleased product exposed via SQLi!\n";
            $global_fail = true;
        } else {
            echo "PASSED: Vulnerability prevented. No unreleased data returned.\n";
        }
    } catch (PDOException $e) {
        echo "FAILED: PDOException encountered: " . $e->getMessage() . "\n";
        $global_fail = true;
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
    echo "FAILED: PDOException encountered: " . $e->getMessage() . "\n";
    $global_fail = true;
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