<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = new mysqli('127.0.0.1', 'root', '', 'seminar_kp');
    if ($conn->connect_error) {
        echo "FAILED: " . $conn->connect_error;
    } else {
        echo "SUCCESS";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage();
}
?>
