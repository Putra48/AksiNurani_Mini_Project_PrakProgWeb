<?php
// ============================================================
// KONEKSI DATABASE - Aksi Nurani
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aksi_nurani');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');

// ============================================================
// SESSION HELPER
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getSession() {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin($redirect = 'login.php') {
    if (!isLoggedIn()) {
        $current = urlencode($_SERVER['REQUEST_URI']);
        header("Location: {$redirect}?redirect={$current}");
        exit;
    }
}

function requirePenyelenggara() {
    requireLogin();
    if (getSession()['role'] !== 'penyelenggara') {
        header("Location: index.php");
        exit;
    }
}

function requireDonatur() {
    requireLogin();
    if (getSession()['role'] !== 'donatur') {
        header("Location: index.php");
        exit;
    }
}

// ============================================================
// HELPERS
// ============================================================
function formatRupiah($num) {
    return 'Rp ' . number_format($num, 0, ',', '.');
}

function getProgress($collected, $target) {
    if ($target <= 0) return 0;
    return min(100, round(($collected / $target) * 100));
}

function getCategoryClass($kategori) {
    $map = [
        'Bencana Alam' => 'bencana',
        'Pendidikan'   => 'pendidikan',
        'Kesehatan'    => 'kesehatan',
        'Lingkungan'   => 'lingkungan',
        'Sosial'       => 'sosial',
    ];
    return $map[$kategori] ?? 'bencana';
}

// Membersihkan input untuk DISIMPAN. Karena semua query memakai prepared
// statement (bind_param) dan semua output sudah di-escape dengan
// htmlspecialchars(), di sini cukup buang tag HTML + trim. JANGAN memanggil
// real_escape_string / htmlspecialchars di sini, karena akan menyebabkan
// double-escaping (mis. "O'Brien" tersimpan jadi "O\'Brien", "&" jadi "&amp;").
// Parameter $conn dipertahankan demi kompatibilitas dengan pemanggil lama.
function sanitize($conn, $str) {
    return trim(strip_tags((string) $str));
}

// ============================================================
// KEAMANAN: redirect aman, CSRF token, gambar placeholder
// ============================================================

// Hanya izinkan redirect ke path lokal relatif (cegah open-redirect ke domain luar).
function safeRedirectTarget($target, $fallback = 'index.php') {
    $target = (string) $target;
    if ($target === '') return $fallback;
    // Tolak URL absolut / protocol-relative (http://, //evil.com, dst.)
    if (preg_match('#^(?:[a-z][a-z0-9+.\-]*:)?//#i', $target)) return $fallback;
    if (strpos($target, "\n") !== false || strpos($target, "\r") !== false) return $fallback;
    if ($target[0] === '/') return $fallback;   // path absolut juga ditolak demi sederhana
    return $target;
}

function redirectTo($target, $fallback = 'index.php') {
    header('Location: ' . safeRedirectTarget($target, $fallback));
    exit;
}

// Token CSRF untuk melindungi aksi yang mengubah data.
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf($token) {
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Placeholder gambar lokal (via.placeholder.com sudah tidak aktif sejak 2024).
function placeholderImg($label = 'Tidak ada gambar') {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="400" viewBox="0 0 800 400">'
         . '<rect width="800" height="400" fill="#e2e8f0"/>'
         . '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" '
         . 'font-family="sans-serif" font-size="28" fill="#94a3b8">'
         . htmlspecialchars($label, ENT_QUOTES) . '</text></svg>';
    return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}

// Menentukan URL gambar kampanye dengan aman (URL eksternal vs file lokal).
function campaignImageSrc($gambar) {
    if (empty($gambar)) return placeholderImg();
    return strpos($gambar, 'http') === 0 ? $gambar : 'uploads/kampanye/' . $gambar;
}

// Login pengguna ke session + cegah session fixation.
function loginSession(array $user) {
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
}

// ============================================================
// RATE LIMITING: Mencegah brute-force login & spam registrasi
// ============================================================
// Cara kerja:
//   1. checkRateLimit('login')     → cek apakah masih boleh mencoba
//   2. recordFailedAttempt('login') → catat percobaan gagal
//   3. resetRateLimit('login')     → reset setelah berhasil login
//
// Default: 5 percobaan gagal → dikunci 5 menit (300 detik)
// ============================================================

/**
 * Cek apakah user masih diizinkan melakukan aksi.
 *
 * @param  string $key          Kunci unik ('login' atau 'register')
 * @param  int    $maxAttempts   Batas percobaan sebelum dikunci
 * @param  int    $lockSeconds   Lama penguncian dalam detik
 * @return array  ['allowed' => bool, 'remaining' => int detik sisa]
 */
function checkRateLimit($key, $maxAttempts = 5, $lockSeconds = 300) {
    $sessionKey = 'rate_limit_' . $key;
    $now = time();

    // Buat data awal jika belum ada
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = [
            'attempts'     => 0,
            'locked_until' => 0,
        ];
    }

    // Jika masih dalam masa kunci → tolak akses
    if ($_SESSION[$sessionKey]['locked_until'] > $now) {
        return [
            'allowed'   => false,
            'remaining' => $_SESSION[$sessionKey]['locked_until'] - $now,
        ];
    }

    return ['allowed' => true, 'remaining' => 0];
}

/**
 * Catat satu percobaan gagal. Kunci akses jika sudah melewati batas.
 */
function recordFailedAttempt($key, $maxAttempts = 5, $lockSeconds = 300) {
    $sessionKey = 'rate_limit_' . $key;

    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = ['attempts' => 0, 'locked_until' => 0];
    }

    $_SESSION[$sessionKey]['attempts']++;

    // Sudah melewati batas → kunci selama $lockSeconds
    if ($_SESSION[$sessionKey]['attempts'] >= $maxAttempts) {
        $_SESSION[$sessionKey]['locked_until'] = time() + $lockSeconds;
        $_SESSION[$sessionKey]['attempts'] = 0; // Reset hitungan
    }
}

/**
 * Reset hitungan rate limit (panggil setelah login/register BERHASIL).
 */
function resetRateLimit($key) {
    unset($_SESSION['rate_limit_' . $key]);
}
