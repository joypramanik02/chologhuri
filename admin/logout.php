<?php

require_once __DIR__ . '/../includes/db.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_name']);

session_regenerate_id(true);

header('Location: login.php');
exit;
