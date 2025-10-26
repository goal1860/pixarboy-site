<?php
require_once __DIR__ . '/config/config.php';

// Destroy session
session_destroy();
redirect('/login.php');

