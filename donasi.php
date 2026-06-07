<?php
require_once 'php/koneksi.php';

if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
if (getSession()['role'] !== 'donatur') {
    header("Location: index.php"); exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$stmt = $conn->prepare("SELECT k.*, p.nama_kantor FROM kampanye k
    JOIN penyelenggara p ON k.penyelenggara_id=p.id
    WHERE k.id=? AND k.deadline >= CURDATE()");
$stmt->bind_param('i', $id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
if (!$c) { header("Location: index.php"); exit; }

$session      = getSession();
$pct          = getProgress($c['dana_terkumpul'], $c['target_dana']);
$catClass     = getCategoryClass($c['kategori']);
$deadline     = date('d M Y', strtotime($c['deadline']));
$gambar       = campaignImageSrc($c['gambar']);
$error        = '';
$success      = false;
$savedNominal = 0;

// Nilai untuk mempertahankan input bila terjadi error
$nama    = $session['nama'];
$email   = $session['email'];
$nominal = 0;
$metode  = '';
$pesan   = '';
$isAnon  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = sanitize($conn, $_POST['nama'] ?? '');
    $email   = sanitize($conn, $_POST['email'] ?? '');
    $nominal = intval($_POST['nominal'] ?? 0);
    $metode  = sanitize($conn, $_POST['metode'] ?? '');
    $pesan   = sanitize($conn, $_POST['pesan'] ?? '');
    $isAnon  = isset($_POST['anonim']) && $_POST['anonim'] === '1';
    $namaTersimpan = $isAnon ? 'Anonim' : $nama;

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];

    if (!verifyCsrf($_POST['csrf_token'] ?? ''))            { $error = 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.'; }
    elseif (!$nama || !$email)                              { $error = 'Harap isi nama dan email.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))     { $error = 'Format email tidak valid.'; }
    elseif ($nominal < 10000)                               { $error = 'Nominal donasi minimal Rp 10.000.'; }
    elseif (!$metode)                                       { $error = 'Harap pilih metode pembayaran.'; }
    elseif (empty($_FILES['bukti']['name'])
            || ($_FILES['bukti']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $error = 'Harap upload bukti transfer.';
    } elseif (($_FILES['bukti']['error'] ?? 1) !== UPLOAD_ERR_OK) {
        $error = 'Gagal mengunggah file (mungkin melebihi batas ukuran server).';
    } else {
        $file = $_FILES['bukti'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validasi tipe konten asli, bukan sekadar ekstensi nama file
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!isset($allowed[$ext]) || $allowed[$ext] !== $mime) {
            $error = 'Format file harus JPG, PNG, atau PDF yang valid.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran file maks 5MB.';
        } else {
            // Nama file acak agar tidak bisa ditebak / ditimpa
            $filename   = 'bukti_' . $session['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $uploadPath = 'uploads/bukti/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $stmt2 = $conn->prepare("INSERT INTO donasi
                    (kampanye_id,donatur_id,nama_donatur,email_donatur,nominal,metode_pembayaran,pesan,bukti_transfer,status)
                    VALUES (?,?,?,?,?,?,?,?,'pending')");
                $stmt2->bind_param('iissdsss', $id, $session['id'], $namaTersimpan, $email, $nominal, $metode, $pesan, $filename);
                if ($stmt2->execute()) {
                    $savedNominal = $nominal;
                    $success = true;
                } else {
                    @unlink($uploadPath);
                    $error = 'Gagal menyimpan donasi.';
                }
            } else {
                $error = 'Gagal upload file. Pastikan folder uploads/bukti/ dapat ditulis.';
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
  <title>Formulir Donasi - Aksi Nurani</title>
  <link rel="icon" href="asset/logo aksi nurani.png">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/donasi.css">
</head>
<body>

<?php include 'php/header.php'; ?>

<?php if ($success): ?>
<div class="success-modal show">
  <div class="success-box">
    <div class="success-icon-wrap">✅</div>
    <h2>Terima Kasih!</h2>
    <p>Donasi Anda untuk</p>
    <p class="success-campaign-name"><?= htmlspecialchars($c['judul']) ?></p>
    <div class="success-amount"><?= formatRupiah($savedNominal) ?></div>
    <p>telah berhasil dikirim 🎉</p>
    <div class="success-progress success-pending">
      <div class="sp-label">⏳ Status Donasi Anda</div>
      <div class="sp-pct sp-pct-pending">MENUNGGU VERIFIKASI</div>
      <p class="sp-note">Dana akan terhitung setelah diverifikasi oleh penyelenggara kampanye.</p>
    </div>
    <a href="index.php" class="btn-modal-back">Kembali ke Beranda</a>
    <a href="riwayat_donasi.php" class="btn-modal-detail">📜 Lihat Riwayat Donasi</a>
  </div>
</div>
<?php endif; ?>

<div class="donasi-wrapper">
  <div class="breadcrumb">
    <a href="index.php">🏠 Beranda</a>
    <span>›</span>
    <a href="detail.php?id=<?= $c['id'] ?>">Detail Kampanye</a>
    <span>›</span>
    <span>Formulir Donasi</span>
  </div>

  <div class="campaign-summary">
    <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($c['judul']) ?>" class="summary-img">
    <div class="summary-body">
      <div class="summary-badge-wrap">
        <span class="badge badge-<?= $catClass ?>"><?= $c['kategori'] ?></span>
      </div>
      <div class="summary-title"><?= htmlspecialchars($c['judul']) ?></div>
      <div class="summary-meta">
        <span>🏛 <?= htmlspecialchars($c['nama_kantor']) ?></span>
        <span>📍 <?= htmlspecialchars($c['lokasi']) ?></span>
        <span>⏰ Deadline: <?= $deadline ?></span>
      </div>
      <div class="summary-funds">
        <div>
          <div class="summary-collected"><?= formatRupiah($c['dana_terkumpul']) ?></div>
          <div class="summary-target">dari <?= formatRupiah($c['target_dana']) ?></div>
        </div>
        <div class="summary-pct"><?= $pct ?>%</div>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" data-width="<?= $pct ?>"></div>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="donasi-error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="form-card">
    <?= csrfField() ?>
    <div class="form-section-header">
      <div class="section-num">1</div>
      <h3>Data Donatur</h3>
    </div>
    <div class="form-section-body">
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" id="f-nama" class="form-control"
               value="<?= htmlspecialchars($isAnon ? $session['nama'] : $nama) ?>"
               placeholder="Nama Anda" <?= $isAnon ? 'disabled' : '' ?>>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($email) ?>" placeholder="email@contoh.com">
      </div>
      <label class="checkbox-group">
        <input type="checkbox" name="anonim" value="1" id="f-anon" onchange="toggleAnon(this)" <?= $isAnon ? 'checked' : '' ?>>
        Tampilkan donasi sebagai <em>Anonim</em>
      </label>
    </div>

    <hr class="form-divider">

    <div class="form-section-header">
      <div class="section-num">2</div>
      <h3>Detail Pembayaran</h3>
    </div>
    <div class="form-section-body">
      <div class="form-group">
        <label>Nominal Donasi Cepat</label>
        <div class="quick-amounts">
          <?php foreach([10000, 25000, 50000, 100000, 250000, 500000] as $n): ?>
            <button type="button" class="quick-btn" data-value="<?= $n ?>"
                    onclick="setAmount(<?= $n ?>)"><?= formatRupiah($n) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-group">
        <label>Atau masukkan nominal lain</label>
        <div class="input-prefix-wrap">
          <span class="prefix-label">Rp</span>
          <input type="number" name="nominal" id="f-nominal" class="prefix-input"
                 placeholder="50000" min="10000" value="<?= $nominal > 0 ? $nominal : '' ?>" oninput="clearQuickBtn()">
        </div>
        <div class="donasi-min-note">Minimal Rp 10.000</div>
      </div>
      <div class="form-group">
        <label>Metode Pembayaran</label>
        <select name="metode" class="form-control">
          <option value="">-- Pilih Metode --</option>
          <?php foreach (['Transfer Bank BCA','Transfer Bank Mandiri','E-Wallet GoPay','E-Wallet DANA'] as $m): ?>
            <option <?= $metode === $m ? 'selected' : '' ?>><?= $m ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Pesan Dukungan <span class="label-optional">(opsional)</span></label>
        <textarea name="pesan" class="form-control donasi-textarea"
                  placeholder="Tulis doa atau semangat untuk mereka..."><?= htmlspecialchars($pesan) ?></textarea>
      </div>
      <div class="form-group">
        <label>Upload Bukti Transfer <span class="label-optional">(PDF/JPG/PNG, Maks. 5MB)</span></label>
        <div class="file-upload-area" id="file-area" onclick="document.getElementById('f-bukti').click()">
          <input type="file" id="f-bukti" name="bukti" accept=".pdf,.jpg,.jpeg,.png"
                 onchange="onFileChange(this)">
          <div class="upload-icon">📎</div>
          <div class="upload-text" id="file-text">Klik untuk upload bukti transfer</div>
          <div class="upload-hint">PDF, JPG, atau PNG • Maks. 5MB</div>
        </div>
      </div>
    </div>

    <div class="submit-btn-wrap">
      <button type="submit" class="btn-submit-donasi">❤️ Kirim Donasi Sekarang</button>
      <div class="security-note">🔒 Data Anda aman &nbsp;|&nbsp; Donasi akan diverifikasi penyelenggara sebelum terhitung</div>
    </div>
  </form>

  <div class="donasi-back-wrap">
    <a href="detail.php?id=<?= $c['id'] ?>" class="donasi-back-link">← Kembali ke Detail Kampanye</a>
  </div>
</div>

<?php include 'php/footer.php'; ?>

<script>
function setAmount(val) {
  document.getElementById('f-nominal').value = val;
  document.querySelectorAll('.quick-btn').forEach(b => {
    b.classList.toggle('active', parseInt(b.dataset.value) === val);
  });
}
function clearQuickBtn() {
  document.querySelectorAll('.quick-btn').forEach(b => b.classList.remove('active'));
}
function toggleAnon(cb) {
  const n = document.getElementById('f-nama');
  n.value    = cb.checked ? 'Anonim' : '<?= addslashes($session['nama']) ?>';
  n.readOnly = cb.checked;
}
function onFileChange(input) {
  const area = document.getElementById('file-area');
  const txt  = document.getElementById('file-text');
  if (input.files.length) {
    area.classList.add('has-file');
    txt.textContent = '✅ ' + input.files[0].name;
  } else {
    area.classList.remove('has-file');
    txt.textContent = 'Klik untuk upload bukti transfer';
  }
}
window.addEventListener('load', () => {
  document.querySelectorAll('.progress-fill').forEach(el => {
    const w = el.dataset.width;
    el.style.width = '0';
    requestAnimationFrame(() => requestAnimationFrame(() => { el.style.width = w + '%'; }));
  });
});
// --- Konfirmasi jika user meninggalkan form yang sudah diisi ---
let formDirty = false;
document.querySelector('.form-card').addEventListener('input', () => formDirty = true);
window.addEventListener('beforeunload', (e) => {
  if (formDirty) { e.preventDefault(); e.returnValue = ''; }
});
// --- Loading state saat submit (mencegah double-click) ---
document.querySelector('.form-card').addEventListener('submit', function() {
  formDirty = false; // jangan trigger beforeunload
  const btn = this.querySelector('.btn-submit-donasi');
  btn.disabled = true;
  btn.textContent = '⏳ Mengirim donasi...';
});
</script>
</body>
</html>
