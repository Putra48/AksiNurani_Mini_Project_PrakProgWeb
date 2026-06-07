<?php
// ============================================================
// HEADER PARTIAL - Aksi Nurani
// Pastikan $session = getSession() sudah dipanggil sebelum include
// ============================================================
?>
<header>
  <a href="index.php" class="logo">
    <img src="asset/logo aksi nurani.png" alt="Logo Aksi Nurani">
    <div class="logo-text">
      <span class="logo-name">Aksi Nurani</span>
      <span class="logo-tagline">Bergerak, Berbagi, Berdampak</span>
    </div>
  </a>
  <nav class="header-nav">
    <a href="index.php" class="nav-link">Beranda</a>
    <?php if ($session): ?>
      <span class="nav-username">👤 <?= htmlspecialchars($session['nama']) ?></span>
      <?php if ($session['role'] === 'penyelenggara'): ?>
        <a href="kelola_kampanye.php" class="nav-link nav-link-kelola">📋 Kelola</a>
      <?php else: ?>
        <a href="riwayat_donasi.php" class="nav-link nav-link-kelola">📜 Riwayat</a>
      <?php endif; ?>
      <a href="logout.php" class="nav-link btn-logout">Logout</a>
    <?php else: ?>
      <a href="login.php" class="nav-link btn-login">Login</a>
    <?php endif; ?>
  </nav>
</header>
