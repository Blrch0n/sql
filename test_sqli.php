<?php
require_once "config/db.php";
require_once "includes/header.php";

echo "<div class='glass-card' style='max-width: 800px; margin: 2rem auto;'>";
echo "<h2>SQL Injection Хамгаалалтын тест (products.php)</h2>";

$test_url = "http://localhost/biydaalt/products.php?search=";
$payload = "' OR 1=1 -- ";

$url = $test_url . urlencode($payload);
echo "<p>Тестлэх URL: <code>$url</code></p>";

try {
    $response = @file_get_contents($url);
    
    if ($response === FALSE) {
         echo "<div class='alert-error'>Холбогдоход алдаа гарлаа. Сервер ажиллаж байгаа эсэхийг шалгана уу.</div>";
    } else {
         if (strpos($response, 'Бүтээгдэхүүн') !== false) {
             // Check if secret product is shown.  "Нууц Бүтээгдэхүүн" should be released=0.
             if (strpos($response, 'Нууц Бүтээгдэхүүн') !== false) {
                  echo "<div class='alert-error'>Алдаа! SQL Injection амжилттай боллоо. 'Нууц Бүтээгдэхүүн' харагдаж байна.</div>";
             } else {
                  echo "<div class='alert-success'>Амжилттай хамгаалагдсан. Орж ирсэн query string нь escaped хийгдэж эсвэл prepared statement ашиглагдсан тул release=0 бүтээгдэхүүн харагдахгүй байна.</div>";
             }
         } else {
             echo "<div class='alert-warning'>Хариу ирсэн боловч хүлээгдэж буй үр дүн гарсангүй.</div>";
         }
    }
} catch (Exception $e) {
    echo "<div class='alert-error'>PDO Exception гарсан нь хамгаалж буйн шинж боловч ердийн хэрэглэгчид алдаа харуулахгүй байх нь зүйтэй. Алдаа: " . esc($e->getMessage()) . "</div>";
}

echo "</div>";
require_once "includes/footer.php";
?>
