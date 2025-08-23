<?php
require_once '../config/config.php';

// Destroy all session data
session_destroy();

// Redirect to home page with success message
redirect('../index.php?logout=success');
?>
