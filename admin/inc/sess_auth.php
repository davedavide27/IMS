<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the session contains user ID
if (!isset($_SESSION['userdata']['id'])) {
    // Optionally set the user ID from some login logic (for demonstration)
    // $_SESSION['userdata']['id'] = $user_id_from_login_process; // Uncomment and implement during login
}

// Determine the protocol and current URL
$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Check if user data is set in the session
if (!isset($_SESSION['userdata']) && !strpos($link, 'login.php') && !strpos($link, 'register.php')) {
    redirect('admin/login.php'); // Redirect to login if user is not logged in
}

// If user data is set and trying to access the login page, redirect to index
if (isset($_SESSION['userdata']) && strpos($link, 'login.php')) {
    redirect('admin/index.php'); // Redirect to index if user is already logged in
}

// Define modules for access control
$module = array('', 'admin', 'faculty', 'student');

// Check access permissions for admin pages
if (isset($_SESSION['userdata'])) {
    // Store the user ID as user_id in the session for further use
    $_SESSION['user_id'] = $_SESSION['userdata']['id']; // Store user ID for later use

    // Check user access permissions
    if (strpos($link, 'index.php') || strpos($link, 'admin/')) {
        // Check user type
        if ($_SESSION['userdata']['login_type'] != 1) {
            echo "<script>alert('Access Denied!');location.replace('" . base_url . $module[$_SESSION['userdata']['login_type']] . "');</script>";
            exit;
        }
    }
}
