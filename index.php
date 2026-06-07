<?php
require_once 'php/koneksi.php';

$kategori = trim($_GET['kategori'] ?? '');
$lokasi   = trim($_GET['lokasi'] ?? '');
$judul    = trim($_GET['judul'] ?? '');
$page     = max(1, intval($_GET['page'] ?? 1));
$per_page = 8;
$offset   = ($page - 1) * $per_page;

$where  = ["k.deadline >= CURDATE()"];
$params = [];
$types  = '';

if ($judul !== '') {
    $where[] = "k.judul LIKE ?";
    $params[] = "%{$judul}%";
    $types .= 's';
}

if ($kategori !== '') {
    $where[] = "k.kategori = ?";
    $params[] = $kategori;
    $types .= 's';
}

if ($lokasi !== '') {
    $where[] = "k.lokasi LIKE ?";
    $params[] = "%{$lokasi}%";
    $types .= 's';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$orderSQL = 'ORDER BY k.deadline ASC, k.dana_terkumpul ASC';

$countSQL = "SELECT COUNT(*) as total FROM kampanye k
             JOIN penyelenggara p ON k.penyelenggara_id = p.id {$whereSQL}";
$stmt = $conn->prepare($countSQL);
if ($types && $params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_rows  = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT k.*, p.nama_kantor,
        (SELECT COUNT(*) FROM donasi d WHERE d.kampanye_id = k.id AND d.status = 'verified') as donor_count
        FROM kampanye k
        JOIN penyelenggara p ON k.penyelenggara_id = p.id
        {$whereSQL} {$orderSQL}
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$allTypes  = $types . 'ii';
$allParams = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$campaigns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$statsRes   = $conn->query("SELECT COUNT(*) as jml, SUM(dana_terkumpul) as total FROM kampanye WHERE deadline >= CURDATE()");
$stats      = $statsRes->fetch_assoc();
$donorStats = $conn->query("SELECT COUNT(*) as jml FROM donasi WHERE status='verified'")->fetch_assoc();
$session    = getSession();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aksi Nurani - Platform Donasi Terpercaya</title>
  <link rel="icon" href="asset/logo aksi nurani.png">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include 'php/header.php'; ?>

<section class="hero fade-in">
  <div class="hero-content">
    <h1>Bersama Kita Bisa<br>Membuat <span>Perubahan</span></h1>
    <p>Platform donasi terpercaya untuk membantu sesama. Setiap rupiah yang Anda berikan adalah harapan nyata bagi mereka yang membutuhkan.</p>
    <a href="#campaigns" class="btn btn-accent">Mulai Berdonasi →</a>
    <div class="hero-stats">
      <div class="hero-stat">
        <span class="num"><?= $stats['jml'] ?></span>
        <span class="lbl">Kampanye Aktif</span>
      </div>
      <div class="hero-stat">
        <span class="num">Rp <?= round(($stats['total'] ?? 0) / 1000000) ?> Jt</span>
        <span class="lbl">Dana Terkumpul</span>
      </div>
      <div class="hero-stat">
        <span class="num"><?= number_format($donorStats['jml'], 0, ',', '.') ?></span>
        <span class="lbl">Donasi Terverifikasi</span>
      </div>
    </div>
  </div>
</section>

<form method="GET" action="index.php" id="filter-form">
  <div class="filter-bar" id="filter-bar">
    <div class="filter-item">
      <label>Judul Kampanye</label>
      <input type="text" name="judul" placeholder="Cari judul..."value="<?= htmlspecialchars($judul) ?>">
    </div>
  
    <div class="filter-item">
      <label>Kategori</label>
      <select name="kategori">
        <option value="">Semua Kategori</option>
        <?php foreach(['Bencana Alam','Pendidikan','Kesehatan','Lingkungan','Sosial'] as $kat): ?>
          <option value="<?= $kat ?>" <?= $kategori===$kat ? 'selected':'' ?>><?= $kat ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-item">
      <label>Lokasi</label>
      <input type="text" name="lokasi" placeholder="Cari lokasi..." value="<?= htmlspecialchars($lokasi) ?>">
    </div>

    <button type="submit" class="filter-reset filter-btn-search">🔍 Cari</button>
    <a href="index.php" class="filter-reset">↺ Reset</a>
  </div>
</form>

<main id="campaigns" class="campaigns-section">
  <p class="results-info">
    Menampilkan <strong><?= count($campaigns) ?></strong> dari <strong><?= $total_rows ?></strong> kampanye aktif
  </p>

  <?php if (empty($campaigns)): ?>
    <div class="campaign-grid">
      <div class="no-result">
        <div class="icon">🔍</div>
        <p>Tidak ada kampanye yang sesuai. <a href="index.php">Reset filter</a></p>
      </div>
    </div>
  <?php else: ?>
    <div class="campaign-grid">
      <?php foreach ($campaigns as $c):
        $pct      = getProgress($c['dana_terkumpul'], $c['target_dana']);
        $catClass = getCategoryClass($c['kategori']);
        $gambar   = campaignImageSrc($c['gambar']);
      ?>
      <div class="campaign-card fade-in" onclick="location.href='detail.php?id=<?= $c['id'] ?>'">
        <div class="card-img-wrap">
          <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($c['judul']) ?>" loading="lazy">
          <span class="card-badge badge badge-<?= $catClass ?>"><?= $c['kategori'] ?></span>
          <span class="card-deadline-chip">⏰ <?= date('d M Y', strtotime($c['deadline'])) ?></span>
        </div>
        <div class="card-body">
          <div class="card-title"><?= htmlspecialchars($c['judul']) ?></div>
          <div class="card-organizer"><?= htmlspecialchars($c['nama_kantor']) ?></div>
          <div class="card-funds">
            <span class="card-collected"><?= formatRupiah($c['dana_terkumpul']) ?></span>
            <span class="card-target">dari <?= formatRupiah($c['target_dana']) ?></span>
          </div>
          <div class="card-progress">
            <div class="card-pct"><?= $pct ?>% tercapai</div>
            <div class="progress-bar">
              <div class="progress-fill" data-width="<?= $pct ?>"></div>
            </div>
          </div>
          <div class="card-footer">
            <span class="card-donors">👥 <?= $c['donor_count'] ?> donatur</span>
            <a href="detail.php?id=<?= $c['id'] ?>" class="btn-card-detail" onclick="event.stopPropagation()">Lihat Detail</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1):
      $qBase = array_filter(['judul'=>$judul,'kategori'=>$kategori,'lokasi'=>$lokasi]);
      $pageUrl = fn($p) => 'index.php?' . http_build_query($qBase + ['page' => $p]);
    ?>
    <div class="pagination-wrap">
      <?php if ($page > 1): ?>
        <a href="<?= htmlspecialchars($pageUrl($page-1)) ?>" class="page-btn">← Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
        <a href="<?= htmlspecialchars($pageUrl($i)) ?>"
           class="page-btn <?= $i===$page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
        <a href="<?= htmlspecialchars($pageUrl($page+1)) ?>" class="page-btn">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php if (!$session || $session['role'] !== 'penyelenggara'): ?>
<section class="cta-section fade-in">
  <h2>Punya Kampanye yang Ingin Digalang?</h2>
  <p>Login sebagai Pengelola Kampanye dan mulai galang dana untuk cause yang Anda percaya.</p>
  <a href="login.php" class="btn-cta-white">Daftar Sekarang</a>
</section>
<?php endif; ?>

<?php include 'php/footer.php'; ?>
</body>
</html>