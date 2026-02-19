<?php
require_once __DIR__.'/config.php';
session_start();
session_destroy();
$redirect = isset($_GET['admin']) ? ADMIN_URL.'/login.php' : SITE_URL.'/login.php';
header('Location: '.$redirect);
exit;
