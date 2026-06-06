<?php
require_once 'php/koneksi.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$stmt = $conn->prepare("SELECT k.*, p.nama_kantor FROM kampanye k
    JOIN penyelenggara p ON k.penyelenggara_id = p.id WHERE k.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
if (!$c) { header("Location: index.php"); exit; }

$donasiStmt = $conn->prepare("SELECT * FROM donasi WHERE kampanye_id=? AND status='verified' ORDER BY created_at DESC LIMIT 5");
$donasiStmt->bind_param('i', $id);
$donasiStmt->execute();
$recentDonasi = $donasiStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$donorCount = $conn->prepare("SELECT COUNT(*) as cnt FROM donasi WHERE kampanye_id=? AND status='verified'");
$donorCount->bind_param('i', $id);
$donorCount->execute();
$totalDonors = $donorCount->get_result()->fetch_assoc()['cnt'];

$pct       = getProgress($c['dana_terkumpul'], $c['target_dana']);
$catClass  = getCategoryClass($c['kategori']);
$deadline  = date('d M Y', strtotime($c['deadline']));
$isExpired = strtotime($c['deadline']) < strtotime(date('Y-m-d'));
$gambar    = campaignImageSrc($c['gambar']);
$session   = getSession();
$rekenings = !empty($c['rekening_info']) ? explode('|', $c['rekening_info']) : ['BCA: 1234-5678-90'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($c['judul']) ?> - Aksi Nurani</title>
  <link rel="icon" href="asset/logo aksi nurani.png">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/detail.css">
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

<div class="container" id="main-container">
  <div class="campaign-content show">
    <div class="breadcrumb">
      <a href="index.php">🏠 Beranda</a>
      <span>›</span>
      <span class="badge badge-<?= $catClass ?>"><?= $c['kategori'] ?></span>
      <span>›</span>
      <span><?= htmlspecialchars($c['judul']) ?></span>
    </div>

    <div class="detail-grid">
      <div class="detail-img-wrap">
        <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($c['judul']) ?>">
        <p class="img-caption">📍 <?= htmlspecialchars($c['lokasi']) ?></p>

        <div class="donor-section">
          <h3>💬 Donasi Terbaru</h3>
          <div class="donor-list">
            <?php if (empty($recentDonasi)): ?>
              <p class="no-donors">Belum ada donasi. Jadilah yang pertama! 💪</p>
            <?php else: foreach ($recentDonasi as $d): ?>
              <div class="donor-item">
                <div class="donor-avatar"><?= strtoupper(mb_substr($d['nama_donatur'],0,1)) ?></div>
                <div class="donor-info">
                  <div class="donor-name"><?= htmlspecialchars($d['nama_donatur']) ?></div>
                  <div class="donor-time"><?= date('d M Y H:i', strtotime($d['created_at'])) ?></div>
                  <?php if ($d['pesan']): ?>
                    <div class="donor-pesan">"<?= htmlspecialchars($d['pesan']) ?>"</div>
                  <?php endif; ?>
                </div>
                <div class="donor-amount"><?= formatRupiah($d['nominal']) ?></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

      <div class="detail-right">
        <h2><?= htmlspecialchars($c['judul']) ?></h2>

        <div class="meta-chips">
          <div class="meta-chip">🏛 <strong><?= htmlspecialchars($c['nama_kantor']) ?></strong></div>
          <div class="meta-chip">📍 <strong><?= htmlspecialchars($c['lokasi']) ?></strong></div>
          <div class="meta-chip"><span class="badge badge-<?= $catClass ?>"><?= $c['kategori'] ?></span></div>
        </div>

        <div class="progress-card">
          <div class="progress-header">
            <div>
              <div class="amount-collected"><?= formatRupiah($c['dana_terkumpul']) ?></div>
              <div class="amount-target">dari target <?= formatRupiah($c['target_dana']) ?></div>
            </div>
            <div class="progress-pct"><?= $pct ?>%</div>
          </div>
          <div class="progress-bar-lg">
            <div class="progress-fill-lg" id="progress-fill"></div>
          </div>
          <div class="progress-meta">
            <span class="donors">👥 <?= $totalDonors ?> donatur</span>
            <span class="deadline">⏰ <?= $deadline ?></span>
          </div>
        </div>

        <div class="about-section">
          <h3>Tentang Kampanye</h3>
          <p><?= nl2br(htmlspecialchars($c['deskripsi'])) ?></p>
          <?php if ($c['deskripsi2']): ?>
            <p><?= nl2br(htmlspecialchars($c['deskripsi2'])) ?></p>
          <?php endif; ?>
        </div>

        <div class="payment-box">
          <h4>🏦 Metode Donasi / Rekening</h4>
          <?php foreach ($rekenings as $rek):
            $parts = explode(':', $rek, 2);
            $icon  = (strpos($rek,'BCA')!==false || strpos($rek,'Mandiri')!==false) ? '🏦' : '💳';
          ?>
          <div class="payment-item">
            <div class="payment-icon"><?= $icon ?></div>
            <div>
              <div class="p-name"><?= htmlspecialchars($parts[0]) ?></div>
              <div class="p-num"><?= htmlspecialchars($parts[1] ?? '') ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($isExpired): ?>
          <div class="login-required-banner">
            <div class="lrb-icon">⏰</div>
            <div class="lrb-text">
              Kampanye ini telah <strong>berakhir</strong> pada <?= $deadline ?> dan tidak lagi menerima donasi.
            </div>
          </div>
        <?php elseif (!$session): ?>
          <div class="login-required-banner">
            <div class="lrb-icon">🔐</div>
            <div class="lrb-text">
              Anda harus <a href="login.php?redirect=<?= urlencode("detail.php?id={$c['id']}") ?>">login terlebih dahulu</a> untuk berdonasi.
            </div>
          </div>
        <?php endif; ?>

        <div class="btn-group">
          <a href="index.php" class="btn-back-detail">← Kembali</a>
          <?php if ($isExpired): ?>
            <span class="btn-donate-now" style="opacity:.5;pointer-events:none;cursor:not-allowed">⏰ Kampanye Berakhir</span>
          <?php elseif ($session && $session['role'] === 'donatur'): ?>
            <a href="donasi.php?id=<?= $c['id'] ?>" class="btn-donate-now">❤️ Donasi Sekarang</a>
          <?php elseif ($session && $session['role'] === 'penyelenggara'): ?>
            <a href="kelola_kampanye.php" class="btn-donate-now btn-donate-kelola">📋 Kelola Kampanye</a>
          <?php else: ?>
            <a href="login.php?redirect=<?= urlencode("donasi.php?id={$c['id']}") ?>" class="btn-donate-now">🔐 Login untuk Donasi</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="main-footer">
  <p>&copy; 2026 <strong>Aksi Nurani</strong> — Platform Donasi Terpercaya &nbsp;|&nbsp; Dibuat dengan ❤️ untuk Indonesia</p>
</footer>

<script>
setTimeout(() => {
  document.getElementById('progress-fill').style.width = '<?= $pct ?>%';
}, 200);
</script>
</body>
</html>
