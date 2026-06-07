<?php
require_once __DIR__ . '/functions.php';
if (is_logged_in()) {
    redirect_dashboard();
}
header('Location: login.php');
exit;
