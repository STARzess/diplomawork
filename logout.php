<?php
require_once __DIR__ . '/functions.php';
logout_user();
flash('Системээс гарлаа.');
header('Location: login.php');
exit;
