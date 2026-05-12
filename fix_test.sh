#!/bin/bash
sed -i 's/echo "PASSED: Query failed safely (likely due to strict typing), no data leaked.\\n";/echo "FAILED: Expected safe empty set, got exception: " . $e->getMessage() . "\\n"; $global_fail = true;/g' test_sqli.php
sed -i 's/echo "PASSED: Query failed safely.\\n";/echo "FAILED: Query threw exception: " . $e->getMessage() . "\\n"; $global_fail = true;/g' test_sqli.php
