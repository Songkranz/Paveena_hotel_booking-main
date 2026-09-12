<?php

session_start();
if (!isset($_SESSION['user_id'])) {

    header("Location: /paveena_hotel_booking/customer/login.php");

    exit();

}

if ($_SESSION['role'] !== 'admin') {

    header("Location: /paveena_hotel_booking/customer/login.php");

    exit();

}

?>