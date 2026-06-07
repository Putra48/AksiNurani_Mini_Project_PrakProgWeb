<?php
require_once 'php/koneksi.php';

// Hapus seluruh data session secara menyeluruh.
$_SESSION = [];

// Hapus cookie session di sisi browser.
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

header("Location: logout_page.php");
exit;
