<?php
$host = "localhost";
$user = "team03";
$pass = "team03";
$dbname = "team03";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}
?>