<?php
require_once '../config/config.php';

// Destroy session
session_destroy();
redirect('/login.php');

