<?php
require_once 'php/koneksi.php';
requirePenyelenggara();

$session = getSession();
$pid     = $session['id'];
$msg     = '';
$msgType = 'success';

// ---- HAPUS KAMPANYE ----
if (isset($_GET['hapus'])) {
    if (!verifyCsrf($_GET['csrf'] ?? '')) {
        $msg = 'Sesi tidak valid. Coba lagi.'; $msgType = 'error';
    } else {
        $kid = intval($_GET['hapus']);
        $cek = $conn->prepare("SELECT dana_terkumpul FROM kampanye WHERE id=? AND penyelenggara_id=?");
        $cek->bind_param('ii', $kid, $pid);
        $cek->execute();
        $row = $cek->get_result()->fetch_assoc();
        if (!$row) {
            $msg = 'Kampanye tidak ditemukan.'; $msgType = 'error';
        } elseif ($row['dana_terkumpul'] >= 10000) {
            $msg = 'Kampanye tidak dapat dihapus karena dana terkumpul ≥ Rp 10.000.'; $msgType = 'error';
        } else {
            $del = $conn->prepare("DELETE FROM kampanye WHERE id=? AND penyelenggara_id=?");
            $del->bind_param('ii', $kid, $pid);
            $del->execute();
            $msg = 'Kampanye berhasil dihapus.';
        }
    }
}

// ---- TAMBAH / EDIT KAMPANYE ----
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.'; $msgType = 'error';
    } else {
        $action   = $_POST['action'];
        $kid      = intval($_POST['kid'] ?? 0);
        $judul    = sanitize($conn, $_POST['judul'] ?? '');
        $kat      = sanitize($conn, $_POST['kategori'] ?? '');
        $lokasi   = sanitize($conn, $_POST['lokasi'] ?? '');
        $desk     = sanitize($conn, $_POST['deskripsi'] ?? '');
        $desk2    = sanitize($conn, $_POST['deskripsi2'] ?? '');
        $target   = floatval($_POST['target_dana'] ?? 0);
        $deadline = sanitize($conn, $_POST['deadline'] ?? '');
        $rek      = sanitize($conn, $_POST['rekening_info'] ?? '');

        $gambarFile = '';
        if (!empty($_FILES['gambar']['name']) && ($_FILES['gambar']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $file    = $_FILES['gambar'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($file['tmp_name']);

            if (isset($allowed[$ext]) && $allowed[$ext] === $mime && $file['size'] <= 5*1024*1024) {
                $fname = 'kampanye_' . $pid . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], 'uploads/kampanye/'.$fname)) {
                    $gambarFile = $fname;
                } else {
                    $msg = 'Gagal menyimpan gambar.'; $msgType = 'error';
                }
            } else {
                $msg = 'Gambar harus JPG/PNG/WebP yang valid dan maks 5MB.'; $msgType = 'error';
            }
        }

        if (!$msg) {
            $gambarFinal = $gambarFile ?: sanitize($conn, $_POST['gambar_url'] ?? '');
            if ($action === 'tambah') {
                $stmt = $conn->prepare("INSERT INTO kampanye (penyelenggara_id,judul,kategori,lokasi,deskripsi,deskripsi2,target_dana,gambar,rekening_info,deadline) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('isssssdsss', $pid,$judul,$kat,$lokasi,$desk,$desk2,$target,$gambarFinal,$rek,$deadline);
                if ($stmt->execute()) {
                    $msg = 'Kampanye berhasil ditambahkan!';
                } else {
                    $msg = 'Gagal menambah kampanye.'; $msgType = 'error';
                }
            } elseif ($action === 'edit' && $kid) {
                if ($gambarFinal) {
                    $stmt = $conn->prepare("UPDATE kampanye SET judul=?,kategori=?,lokasi=?,deskripsi=?,deskripsi2=?,target_dana=?,gambar=?,rekening_info=?,deadline=? WHERE id=? AND penyelenggara_id=?");
                    $stmt->bind_param('sssssdsssii',$judul,$kat,$lokasi,$desk,$desk2,$target,$gambarFinal,$rek,$deadline,$kid,$pid);
                } else {
                    $stmt = $conn->prepare("UPDATE kampanye SET judul=?,kategori=?,lokasi=?,deskripsi=?,deskripsi2=?,target_dana=?,rekening_info=?,deadline=? WHERE id=? AND penyelenggara_id=?");
                    $stmt->bind_param('sssssdssii',$judul,$kat,$lokasi,$desk,$desk2,$target,$rek,$deadline,$kid,$pid);
                }
                if ($stmt->execute()) {
                    $msg = 'Kampanye berhasil diperbarui!';
                } else {
                    $msg = 'Gagal memperbarui.'; $msgType = 'error';
                }
            }
        }
    }
}

// ---- VERIFIKASI / TOLAK DONASI ----
if (isset($_GET['verify'])) {
    if (!verifyCsrf($_GET['csrf'] ?? '')) {
        $msg = 'Sesi tidak valid. Coba lagi.'; $msgType = 'error';
    } else {
        $did    = intval($_GET['verify']);
        $status = $_GET['status'] ?? '';
        if (in_array($status, ['verified','ditolak'])) {
            $cek = $conn->prepare("SELECT d.* FROM donasi d JOIN kampanye k ON d.kampanye_id=k.id WHERE d.id=? AND k.penyelenggara_id=?");
            $cek->bind_param('ii', $did, $pid);
            $cek->execute();
            $don = $cek->get_result()->fetch_assoc();
            // Hanya proses bila masih 'pending' — mencegah double-counting dana saat link diklik/refresh ulang.
            if ($don && $don['status'] === 'pending') {
                if ($status === 'verified') {
                    $upd = $conn->prepare("UPDATE donasi SET status='verified', verified_at=NOW() WHERE id=? AND status='pending'");
                    $upd->bind_param('i', $did); $upd->execute();
                    if ($upd->affected_rows === 1) {
                        $add = $conn->prepare("UPDATE kampanye SET dana_terkumpul=dana_terkumpul+? WHERE id=?");
                        $add->bind_param('di', $don['nominal'], $don['kampanye_id']); $add->execute();
                        $msg = 'Donasi diterima! Dana terkumpul bertambah.';
                    }
                } else {
                    $upd = $conn->prepare("UPDATE donasi SET status='ditolak' WHERE id=? AND status='pending'");
                    $upd->bind_param('i', $did); $upd->execute();
                    $msg = 'Donasi telah ditolak.'; $msgType = 'error';
                }
            } else {
                $msg = 'Donasi tidak ditemukan atau sudah diproses.'; $msgType = 'error';
            }
        }
    }
}

// ---- FETCH DATA ----
$kampanyeStmt = $conn->prepare("SELECT k.*,
    (SELECT COUNT(*) FROM donasi d WHERE d.kampanye_id=k.id AND d.status='verified') as verified_count,
    (SELECT COUNT(*) FROM donasi d WHERE d.kampanye_id=k.id AND d.status='pending') as pending_count,
    (SELECT SUM(d.nominal) FROM donasi d WHERE d.kampanye_id=k.id AND d.status='pending') as dana_pending
    FROM kampanye k WHERE k.penyelenggara_id=? ORDER BY k.created_at DESC");
$kampanyeStmt->bind_param('i', $pid);
$kampanyeStmt->execute();
$kampanyes = $kampanyeStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$editData = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $eStmt = $conn->prepare("SELECT * FROM kampanye WHERE id=? AND penyelenggara_id=?");
    $eStmt->bind_param('ii', $eid, $pid); $eStmt->execute();
    $editData = $eStmt->get_result()->fetch_assoc();
}

$viewDonasi = null; $donasiList = [];
if (isset($_GET['donasi'])) {
    $dkid = intval($_GET['donasi']);
    $vStmt = $conn->prepare("SELECT * FROM kampanye WHERE id=? AND penyelenggara_id=?");
    $vStmt->bind_param('ii', $dkid, $pid); $vStmt->execute();
    $viewDonasi = $vStmt->get_result()->fetch_assoc();
    if ($viewDonasi) {
        $dlStmt = $conn->prepare("SELECT * FROM donasi WHERE kampanye_id=? ORDER BY created_at DESC");
        $dlStmt->bind_param('i', $dkid); $dlStmt->execute();
        $donasiList = $dlStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Kampanye - Aksi Nurani</title>
  <link rel="icon" href="asset/logo aksi nurani.png">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/kelola_kampanye.css">
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
    <span class="nav-username">👤 <?= htmlspecialchars($session['nama']) ?></span>
    <a href="logout.php" class="nav-link btn-logout">logout</a>
  </nav>
</header>

<div class="kelola-wrap">
  <div class="page-header">
    <h2>📋 Kelola Kampanye Saya</h2>
    <a href="kelola_kampanye.php?form=tambah" class="btn-tambah">+ Tambah Kampanye Baru</a>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType==='error' ? 'error' : 'success' ?>">
      <?= $msgType==='error' ? '⚠️' : '✅' ?> <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <!-- FORM TAMBAH / EDIT -->
  <?php if (isset($_GET['form']) || $editData): ?>
  <div class="form-card-kelola">
    <h3><?= $editData ? '✏️ Edit Kampanye' : '➕ Tambah Kampanye Baru' ?></h3>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'tambah' ?>">
      <?php if ($editData): ?>
        <input type="hidden" name="kid" value="<?= $editData['id'] ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group form-full">
          <label>Judul Kampanye *</label>
          <input type="text" name="judul" class="form-control" required
                 value="<?= htmlspecialchars($editData['judul'] ?? '') ?>"
                 placeholder="Judul lengkap kampanye">
        </div>
        <div class="form-group">
          <label>Kategori *</label>
          <select name="kategori" class="form-control" required>
            <option value="">-- Pilih Kategori --</option>
            <?php foreach(['Bencana Alam','Pendidikan','Kesehatan','Lingkungan','Sosial'] as $kat): ?>
              <option value="<?= $kat ?>" <?= ($editData['kategori'] ?? '')===$kat ? 'selected' : '' ?>><?= $kat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Lokasi *</label>
          <input type="text" name="lokasi" class="form-control" required
                 value="<?= htmlspecialchars($editData['lokasi'] ?? '') ?>"
                 placeholder="Kota, Provinsi">
        </div>
        <div class="form-group">
          <label>Target Dana (Rp) *</label>
          <input type="number" name="target_dana" class="form-control" required
                 min="10000" value="<?= $editData['target_dana'] ?? '' ?>"
                 placeholder="50000000">
        </div>
        <div class="form-group">
          <label>Deadline *</label>
          <input type="date" name="deadline" class="form-control" required
                 value="<?= $editData['deadline'] ?? '' ?>"
                 min="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group form-full">
          <label>Deskripsi Utama *</label>
          <textarea name="deskripsi" class="form-control kelola-textarea" required
                    placeholder="Deskripsi lengkap kampanye..."><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
        </div>
        <div class="form-group form-full">
          <label>Deskripsi Tambahan <span class="form-optional">(opsional)</span></label>
          <textarea name="deskripsi2" class="form-control kelola-textarea-sm"
                    placeholder="Ajakan atau informasi tambahan..."><?= htmlspecialchars($editData['deskripsi2'] ?? '') ?></textarea>
        </div>
        <div class="form-group form-full">
          <label>Gambar Kampanye <span class="form-optional">(Upload file atau URL)</span></label>
          <input type="file" name="gambar" class="form-control kelola-file-input"
                 accept=".jpg,.jpeg,.png,.webp">
          <input type="text" name="gambar_url" class="form-control kelola-url-input"
                 placeholder="Atau URL gambar: https://..."
                 value="<?= (isset($editData['gambar']) && strpos($editData['gambar'],'http')===0) ? htmlspecialchars($editData['gambar']) : '' ?>">
          <?php if (!empty($editData['gambar'])): ?>
            <div class="gambar-current">Gambar saat ini: <?= htmlspecialchars($editData['gambar']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group form-full">
          <label>Info Rekening <span class="form-optional">(pisahkan dengan |)</span></label>
          <input type="text" name="rekening_info" class="form-control"
                 placeholder="BCA: 1234-5678-90|Mandiri: 0987-6543-21|GoPay/DANA: 0812-3456-7890"
                 value="<?= htmlspecialchars($editData['rekening_info'] ?? 'BCA: 1234-5678-90 a.n Aksi Nurani|Mandiri: 0987-6543-21 a.n Aksi Nurani|GoPay/DANA: 0812-3456-7890') ?>">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-tambah"><?= $editData ? '💾 Simpan Perubahan' : '➕ Tambah Kampanye' ?></button>
        <a href="kelola_kampanye.php" class="btn-batal">✕ Batal</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- PANEL DONASI PER KAMPANYE -->
  <?php if ($viewDonasi): ?>
  <div class="donasi-panel">
    <div class="donasi-panel-header">
      <h3>💬 Donasi: <?= htmlspecialchars($viewDonasi['judul']) ?></h3>
      <a href="kelola_kampanye.php" class="donasi-panel-back">← Kembali</a>
    </div>

    <?php
    $tV = array_sum(array_column(array_filter($donasiList, fn($d) => $d['status']==='verified'), 'nominal'));
    $tP = array_sum(array_column(array_filter($donasiList, fn($d) => $d['status']==='pending'), 'nominal'));
    $tD = array_sum(array_column(array_filter($donasiList, fn($d) => $d['status']==='ditolak'), 'nominal'));
    $cV = count(array_filter($donasiList, fn($d) => $d['status']==='verified'));
    $cP = count(array_filter($donasiList, fn($d) => $d['status']==='pending'));
    $cD = count(array_filter($donasiList, fn($d) => $d['status']==='ditolak'));
    ?>
    <div class="summary-badges">
      <span class="summary-badge sb-verified">✅ Verified: <?= formatRupiah($tV) ?> (<?= $cV ?> donasi)</span>
      <span class="summary-badge sb-pending">⏳ Pending: <?= formatRupiah($tP) ?> (<?= $cP ?> donasi)</span>
      <span class="summary-badge sb-ditolak">❌ Ditolak: <?= formatRupiah($tD) ?> (<?= $cD ?> donasi)</span>
    </div>

    <?php if (empty($donasiList)): ?>
      <p class="table-empty">Belum ada donasi untuk kampanye ini.</p>
    <?php else: ?>
    <div class="table-wrap table-no-margin table-no-radius">
      <table>
        <thead>
          <tr>
            <th>Donatur</th>
            <th>Nominal</th>
            <th>Metode</th>
            <th>Pesan</th>
            <th>Bukti</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($donasiList as $d): ?>
          <tr>
            <td>
              <div class="cell-title"><?= htmlspecialchars($d['nama_donatur']) ?></div>
              <div class="cell-sub"><?= date('d M Y H:i', strtotime($d['created_at'])) ?></div>
            </td>
            <td class="cell-amount"><?= formatRupiah($d['nominal']) ?></td>
            <td><?= htmlspecialchars($d['metode_pembayaran']) ?></td>
            <td class="cell-italic"><?= $d['pesan'] ? '"'.htmlspecialchars($d['pesan']).'"' : '<span class="cell-muted">-</span>' ?></td>
            <td>
              <?php if ($d['bukti_transfer'] && file_exists('uploads/bukti/'.$d['bukti_transfer'])): ?>
                <a href="uploads/bukti/<?= htmlspecialchars($d['bukti_transfer']) ?>"
                   target="_blank" rel="noopener" class="btn-sm btn-sm-edit">📎 Lihat</a>
              <?php else: ?>
                <span class="cell-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge-status badge-<?= $d['status'] ?>">
                <?= $d['status']==='verified' ? '✅ Verified' : ($d['status']==='pending' ? '⏳ Pending' : '❌ Ditolak') ?>
              </span>
            </td>
            <td>
              <?php if ($d['status']==='pending'): ?>
                <a href="kelola_kampanye.php?donasi=<?= $viewDonasi['id'] ?>&verify=<?= $d['id'] ?>&status=verified&csrf=<?= csrfToken() ?>"
                   class="btn-sm btn-sm-donasi"
                   onclick="return confirm('Verifikasi donasi ini? Dana akan ditambahkan.')">✅ Terima</a>
                <a href="kelola_kampanye.php?donasi=<?= $viewDonasi['id'] ?>&verify=<?= $d['id'] ?>&status=ditolak&csrf=<?= csrfToken() ?>"
                   class="btn-sm btn-sm-del"
                   onclick="return confirm('Tolak donasi ini?')">❌ Tolak</a>
              <?php else: ?>
                <span class="cell-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- TABEL DAFTAR KAMPANYE -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Kampanye</th>
          <th>Kategori</th>
          <th>Target</th>
          <th>Terkumpul</th>
          <th>Deadline</th>
          <th>Donasi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($kampanyes)): ?>
        <tr>
          <td colspan="8" class="table-empty">
            Belum ada kampanye. <a href="kelola_kampanye.php?form=tambah">Tambah sekarang</a>.
          </td>
        </tr>
      <?php else: foreach ($kampanyes as $i => $k):
        $pct       = getProgress($k['dana_terkumpul'], $k['target_dana']);
        $isExpired = strtotime($k['deadline']) < time();
      ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td>
            <div class="cell-title">
              <?= htmlspecialchars($k['judul']) ?>
              <?php if ($isExpired): ?>
                <span class="badge-status badge-expired">Expired</span>
              <?php endif; ?>
            </div>
            <div class="cell-lokasi">📍 <?= htmlspecialchars($k['lokasi']) ?></div>
          </td>
          <td><span class="badge badge-<?= getCategoryClass($k['kategori']) ?>"><?= $k['kategori'] ?></span></td>
          <td><?= formatRupiah($k['target_dana']) ?></td>
          <td>
            <div class="cell-amount"><?= formatRupiah($k['dana_terkumpul']) ?></div>
            <div class="cell-pct"><?= $pct ?>%</div>
            <?php if ($k['dana_pending']): ?>
              <div class="cell-pending">⏳ <?= formatRupiah($k['dana_pending']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= date('d M Y', strtotime($k['deadline'])) ?></td>
          <td>
            <a href="kelola_kampanye.php?donasi=<?= $k['id'] ?>" class="btn-sm btn-sm-donasi">
              👥 <?= $k['verified_count'] ?> Donatur
              <?php if ($k['pending_count'] > 0): ?>
                <span class="pending-chip"><?= $k['pending_count'] ?></span>
              <?php endif; ?>
            </a>
          </td>
          <td>
            <a href="kelola_kampanye.php?edit=<?= $k['id'] ?>" class="btn-sm btn-sm-edit">✏️ Edit</a>
            <a href="kelola_kampanye.php?hapus=<?= $k['id'] ?>&csrf=<?= csrfToken() ?>" class="btn-sm btn-sm-del"
               onclick="return confirm('Hapus kampanye ini? Tindakan tidak dapat dibatalkan.')">🗑️ Hapus</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer class="main-footer">
  <p>&copy; 2026 <strong>Aksi Nurani</strong> — Platform Donasi Terpercaya &nbsp;|&nbsp; Dibuat dengan ❤️ untuk Indonesia</p>
</footer>
</body>
</html>
