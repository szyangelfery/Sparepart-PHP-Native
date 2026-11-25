<?php
require_once 'includes/auth.php';
logActivity($conn, 'LOGOUT', 'users', currentUserId(), 'Logout');
session_destroy();
redirect('auth/login.php');