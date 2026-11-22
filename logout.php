<?php 
require_once 'inc/security.php';
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
session_unset();
session_destroy();
header("location:index.php");
exit;
?>
