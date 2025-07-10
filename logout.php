<?php
session_start();
require_once 'includes/auth.php';

// Use Auth class for proper logout logging
$auth = new Auth();
$auth->logout();
?> 