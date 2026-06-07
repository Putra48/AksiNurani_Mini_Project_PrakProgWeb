<?php
require_once 'php/koneksi.php';
requireLogin();

$session = getSession();
if ($session['role'] !== 'donatur') {
    header("Location: index.php"); exit;
}

$donaturId = $session['id'];

$stmt = $conn->prepare("
    SELECT d.*, k.judul, k.id as kid, p.nama_kantor
    FROM donasi d
    JOIN kampanye k ON d.kampanye_id = k.id
    JOIN penyelenggara p ON k.penyelenggara_id = p.id
    WHERE d.donatur_id = ?
    ORDER BY d.created_at DESC
");
$stmt->bind_param('i', $donaturId);
$stmt->execute();
$riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$tV = array_sum(array_column(array_filter($riwayat, fn($d) => $d['status']==='verified'), 'nominal'));
$tP = array_sum(array_column(array_filter($riwayat, fn($d) => $d['status']==='pending'), 'nominal'));
$tD = array_sum(array_column(array_filter($riwayat, fn($d) => $d['status']==='ditolak'), 'nominal'));
$cV = count(array_filter($riwayat, fn($d) => $d['status']==='verified'));
$cP = count(array_filter($riwayat, fn($d) => $d['status']==='pending'));
$cD = count(array_filter($riwayat, fn($d) => $d['status']==='ditolak'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Donasi - Aksi Nurani</title>
  <link rel="icon" href="asset/logo aksi nurani.png">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/riwayat_donasi.css">
</head>
<body>

<?php include 'php/header.php'; ?>

<div class="riwayat-wrap">
  <h2 class="page-title">📜 Riwayat Donasi Saya</h2>

  <!-- RINGKASAN (BONUS FITUR) -->
  <div class="ringkasan-grid fade-in">
    <div class="ringkasan-card r-verified">
      <div class="r-icon">✅</div>
      <div class="r-amount"><?= formatRupiah($tV) ?></div>
      <div class="r-label"><?= $cV ?> donasi Terverifikasi</div>
    </div>
    <div class="ringkasan-card r-pending">
      <div class="r-icon">⏳</div>
      <div class="r-amount"><?= formatRupiah($tP) ?></div>
      <div class="r-label"><?= $cP ?> donasi Pending</div>
    </div>
    <div class="ringkasan-card r-ditolak">
      <div class="r-icon">❌</div>
      <div class="r-amount"><?= formatRupiah($tD) ?></div>
      <div class="r-label"><?= $cD ?> donasi Ditolak</div>
    </div>
  </div>

  <?php if (empty($riwayat)): ?>
    <div class="empty-state">
      <div class="empty-icon">💝</div>
      <p>Anda belum pernah berdonasi.</p>
      <a href="index.php" class="btn btn-accent">Mulai Berdonasi →</a>
    </div>
  <?php else: ?>
  <div class="riwayat-list">
    <?php foreach ($riwayat as $d):
      $label = $d['status']==='verified' ? 'Terverifikasi'
             : ($d['status']==='pending'  ? 'Menunggu Verifikasi' : 'Ditolak');
      $icon  = $d['status']==='verified' ? '✅'
             : ($d['status']==='pending'  ? '⏳' : '❌');
    ?>
    <div class="riwayat-item fade-in">
      <div class="riwayat-header">
        <div>
          <a href="detail.php?id=<?= $d['kid'] ?>" class="riwayat-campaign-link">
            <?= htmlspecialchars($d['judul']) ?>
          </a>
          <div class="riwayat-organizer">🏛 <?= htmlspecialchars($d['nama_kantor']) ?></div>
        </div>
        <span class="status-chip sc-<?= $d['status'] ?>"><?= $icon ?> <?= $label ?></span>
      </div>
      <div class="riwayat-body">
        <div class="riwayat-body-item">
          <label>Nominal Donasi</label>
          <span class="nominal-big"><?= formatRupiah($d['nominal']) ?></span>
        </div>
        <div class="riwayat-body-item">
          <label>Metode Pembayaran</label>
          <span><?= htmlspecialchars($d['metode_pembayaran']) ?></span>
        </div>
        <div class="riwayat-body-item">
          <label>Tanggal Donasi</label>
          <span><?= date('d M Y H:i', strtotime($d['created_at'])) ?></span>
        </div>
        <div class="riwayat-body-item">
          <label>Nama Donatur</label>
          <span><?= htmlspecialchars($d['nama_donatur']) ?></span>
        </div>
        <?php if ($d['pesan']): ?>
        <div class="riwayat-body-item riwayat-body-full">
          <label>Pesan Dukungan</label>
          <span class="pesan-italic">"<?= htmlspecialchars($d['pesan']) ?>"</span>
        </div>
        <?php endif; ?>
        <?php if ($d['status']==='verified' && $d['verified_at']): ?>
        <div class="riwayat-body-item riwayat-body-full">
          <label>Diverifikasi pada</label>
          <span class="verified-at">✅ <?= date('d M Y H:i', strtotime($d['verified_at'])) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'php/footer.php'; ?>

</body>
</html>
