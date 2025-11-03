<?php
// Database connection (adjust credentials for your environment)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '@2004Keren14';
$DB_NAME = 'schoolUniformdb';


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset('utf8mb4');
} catch (Exception $e) {
http_response_code(500);
exit('Database connection failed.');
}
?>
