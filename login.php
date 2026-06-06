<?php
require_once 'php/koneksi.php';

if (isLoggedIn()) {
    redirectTo($_GET['redirect'] ?? '');
}

$error     = '';
$activeTab = $_GET['tab'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.';
        $action = '';
    }

    if ($action === 'login') {
        $email    = sanitize($conn, $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';

        if (!$email || !$password || !$role) {
            $error = 'Harap isi semua kolom.';
        } else {
            $table = $role === 'donatur' ? 'donatur' : 'penyelenggara';
            $stmt  = $conn->prepare("SELECT * FROM {$table} WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                loginSession([
                    'id'    => $user['id'],
                    'nama'  => $user['nama'] ?? $user['nama_kantor'],
                    'email' => $user['email'],
                    'role'  => $role,
                ]);
                redirectTo($_GET['redirect'] ?? '');
            } else {
                $error = 'Email, password, atau role tidak sesuai.';
            }
        }
    }

    if ($action === 'register') {
        $role     = $_POST['reg_role'] ?? '';
        $nama     = sanitize($conn, $_POST['reg_nama'] ?? '');
        $email    = sanitize($conn, $_POST['reg_email'] ?? '');
        $telp     = sanitize($conn, $_POST['reg_telp'] ?? '');
        $alamat   = sanitize($conn, $_POST['reg_alamat'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirm  = $_POST['reg_confirm'] ?? '';

        if (!$nama || !$email || !$password || !$role || !$telp) {
            $error = 'Harap isi semua kolom wajib.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $confirm) {
            $error = 'Konfirmasi password tidak sesuai.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            if ($role === 'donatur') {
                $stmt = $conn->prepare("INSERT INTO donatur (nama,email,password,no_telepon) VALUES (?,?,?,?)");
                $stmt->bind_param('ssss', $nama, $email, $hashed, $telp);
            } else {
                $stmt = $conn->prepare("INSERT INTO penyelenggara (nama_kantor,email,password,no_telepon,alamat) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sssss', $nama, $email, $hashed, $telp, $alamat);
            }
            if ($stmt->execute()) {
                loginSession(['id'=>$conn->insert_id,'nama'=>$nama,'email'=>$email,'role'=>$role]);
                redirectTo($_GET['redirect'] ?? '');
            } else {
                $error = 'Email sudah terdaftar atau terjadi kesalahan.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Aksi Nurani</title>
  <link rel="icon" href="asset/logo aksi nurani.png">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/login.css">
</head>
<body>

<header>
  <a href="index.php" class="logo">
    <img src="asset/logo aksi nurani.png" alt="Logo">
    <div class="logo-text">
      <span class="logo-name">Aksi Nurani</span>
      <span class="logo-tagline">Bergerak, Berbagi, Berdampak</span>
    </div>
  </a>
  <nav class="header-nav">
    <a href="index.php" class="nav-link">← Kembali ke Beranda</a>
  </nav>
</header>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-brand">
      <img src="asset/logo aksi nurani.png" alt="Logo">
      <h1>Aksi Nurani</h1>
      <p>Masuk untuk mulai berdonasi dan membuat perubahan</p>
    </div>

    <div class="auth-tabs">
      <button class="auth-tab <?= $activeTab==='login'?'active':'' ?>" onclick="switchTab('login')">Masuk</button>
      <button class="auth-tab <?= $activeTab==='register'?'active':'' ?>" onclick="switchTab('register')">Daftar</button>
    </div>

    <div class="auth-body">
      <?php if ($error): ?>
        <div class="error-msg show">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="tab-pane <?= $activeTab==='login'?'active':'' ?>" id="tab-login">
        <form method="POST" action="login.php?<?= http_build_query(['redirect'=>$_GET['redirect']??'']) ?>">
          <input type="hidden" name="action" value="login">
          <?= csrfField() ?>
          <div class="form-group">
            <label>Login Sebagai</label>
            <div class="role-select">
              <label class="role-option">
                <input type="radio" name="role" value="donatur" checked>
                <span class="role-icon">🤝</span>
                <span class="role-label">Donatur</span>
              </label>
              <label class="role-option">
                <input type="radio" name="role" value="penyelenggara">
                <span class="role-icon">📋</span>
                <span class="role-label">Pengelola Kampanye</span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label>Email</label>
            <div class="input-icon-wrap">
              <span class="input-icon">✉️</span>
              <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required>
            </div>
          </div>
          <div class="form-group">
            <label>Password</label>
            <div class="input-icon-wrap">
              <span class="input-icon">🔒</span>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
          </div>
          <button type="submit" class="btn-submit">Masuk Sekarang</button>
          <div class="demo-hint">
            💡 <strong>Demo Donatur:</strong> budi@gmail.com / donatur123<br>
            💡 <strong>Demo Pengelola:</strong> relawan@aksinurani.id / admin123
          </div>
          <a href="index.php" class="back-home">← Kembali ke Beranda</a>
        </form>
      </div>

      <div class="tab-pane <?= $activeTab==='register'?'active':'' ?>" id="tab-register">
        <form method="POST" action="login.php?tab=register&<?= http_build_query(['redirect'=>$_GET['redirect']??'']) ?>">
          <input type="hidden" name="action" value="register">
          <?= csrfField() ?>
          <div class="form-group">
            <label>Daftar Sebagai</label>
            <div class="role-select">
              <label class="role-option">
                <input type="radio" name="reg_role" value="donatur" checked onchange="toggleAlamat(this.value)">
                <span class="role-icon">🤝</span>
                <span class="role-label">Donatur</span>
              </label>
              <label class="role-option">
                <input type="radio" name="reg_role" value="penyelenggara" onchange="toggleAlamat(this.value)">
                <span class="role-icon">📋</span>
                <span class="role-label">Pengelola</span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label id="nama-label">Nama Lengkap</label>
            <div class="input-icon-wrap">
              <span class="input-icon">👤</span>
              <input type="text" name="reg_nama" class="form-control" placeholder="Nama Anda" required>
            </div>
          </div>
          <div class="form-group">
            <label>Email</label>
            <div class="input-icon-wrap">
              <span class="input-icon">✉️</span>
              <input type="email" name="reg_email" class="form-control" placeholder="email@contoh.com" required>
            </div>
          </div>
          <div class="form-group">
            <label>No. Telepon</label>
            <div class="input-icon-wrap">
              <span class="input-icon">📱</span>
              <input type="text" name="reg_telp" class="form-control" placeholder="08xxxxxxxxxx" required>
            </div>
          </div>
          <div class="form-group reg-alamat-group" id="alamat-group">
            <label>Alamat Kantor</label>
            <div class="input-icon-wrap">
              <span class="input-icon">📍</span>
              <input type="text" name="reg_alamat" class="form-control" placeholder="Alamat lengkap">
            </div>
          </div>
          <div class="form-group">
            <label>Password</label>
            <div class="input-icon-wrap">
              <span class="input-icon">🔒</span>
              <input type="password" name="reg_password" class="form-control" placeholder="Min. 6 karakter" required>
            </div>
          </div>
          <div class="form-group">
            <label>Konfirmasi Password</label>
            <div class="input-icon-wrap">
              <span class="input-icon">🔒</span>
              <input type="password" name="reg_confirm" class="form-control" placeholder="Ulangi password" required>
            </div>
          </div>
          <button type="submit" class="btn-submit">Buat Akun</button>
          <a href="index.php" class="back-home">← Kembali ke Beranda</a>
        </form>
      </div>
    </div>
  </div>
</div>

<footer class="main-footer">
  <p>&copy; 2026 <strong>Aksi Nurani</strong> — Platform Donasi Terpercaya &nbsp;|&nbsp; Dibuat dengan ❤️ untuk Indonesia</p>
</footer>

<script>
function switchTab(tab) {
  document.querySelectorAll('.auth-tab').forEach((t, i) => {
    t.classList.toggle('active', (i===0&&tab==='login')||(i===1&&tab==='register'));
  });
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-'+tab).classList.add('active');
}
function toggleAlamat(role) {
  document.getElementById('alamat-group').style.display = role==='penyelenggara' ? 'block' : 'none';
  document.getElementById('nama-label').textContent = role==='penyelenggara' ? 'Nama Kantor / Organisasi' : 'Nama Lengkap';
}
// Sembunyikan field alamat secara default (donatur default)
document.getElementById('alamat-group').style.display = 'none';
</script>
</body>
</html>
