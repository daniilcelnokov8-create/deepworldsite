<?php
session_start();

function require_auth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../api/login.php');
        exit();
    }
}

function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }
}
?>