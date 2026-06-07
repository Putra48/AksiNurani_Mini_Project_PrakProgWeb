## 🗂️ Struktur Project

```
AksiNurani/
├── index.php               → Beranda (daftar kampanye, search, filter, pagination)
├── login.php               → Login & Registrasi (Donatur / Penyelenggara)
├── logout.php              → Proses logout (destroy session)
├── logout_page.php         → Halaman konfirmasi setelah logout
├── detail.php              → Detail kampanye (dari DB)
├── donasi.php              → Formulir donasi (wajib login sebagai Donatur)
├── kelola_kampanye.php     → CRUD kampanye + verifikasi donasi (Penyelenggara)
├── riwayat_donasi.php      → Riwayat & ringkasan donasi (Donatur)
├── database.sql            → Schema + data awal database
├── php/
│   └── koneksi.php         → Koneksi DB + helper session/auth
├── css/                    → Semua stylesheet (tidak diubah dari MP1)
├── js/data.js              → (warisan MP1, tidak digunakan aktif)
├── asset/                  → Logo dan gambar statis
└── uploads/
    ├── bukti/              → Upload bukti transfer donasi
    └── kampanye/           → Upload gambar kampanye
```

---

## ⚙️ Cara Instalasi (XAMPP / Laragon)

### 1. Import Database

Buka **phpMyAdmin** → klik **Import** → pilih file `database.sql` → klik **Go**.

Atau via terminal:
```bash
mysql -u root -p < database.sql
```

### 2. Letakkan Project

Salin seluruh folder `AksiNurani` ke:
- **XAMPP:** `C:/xampp/htdocs/AksiNurani`
- **Laragon:** `C:/laragon/www/AksiNurani`

### 3. Konfigurasi Koneksi DB

Buka `php/koneksi.php`, sesuaikan jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // isi password jika ada
define('DB_NAME', 'aksi_nurani');
```

### 4. Permission Folder Uploads

Pastikan folder berikut dapat ditulis (writable):
```
uploads/bukti/
uploads/kampanye/
```

### 5. Akses

Buka browser: `http://localhost/AksiNurani/`

---

## 🔑 Akun Demo

| Role | Email | Password |
|---|---|---|
| **Donatur** | budi@gmail.com | donatur123 |
| **Donatur** | siti@gmail.com | donatur123 |
| **Donatur** | ahmad@gmail.com | donatur123 |
| **Penyelenggara** | relawan@aksinurani.id | admin123 |
| **Penyelenggara** | yayasan@aksinurani.id | admin123 |
| **Penyelenggara** | rina@aksinurani.id | admin123 |
| **Penyelenggara** | hijau@aksinurani.id | admin123 |
| **Penyelenggara** | sosial@aksinurani.id | admin123 |

---

## ✅ Fitur yang Diimplementasi

### Halaman Utama (index.php)
- [x] Data kampanye dari DB (bukan statis)
- [x] Kampanye expired (lewat deadline) **tidak ditampilkan**
- [x] Search berdasarkan Judul, Kategori, dan Lokasi
- [x] Filter: Kategori, Lokasi, Urutkan
- [x] Urutan default: deadline terdekat + dana terkecil
- [x] **Pagination** (6 kampanye per halaman)
- [x] Hero stats dinamis dari DB

### Login & Logout (login.php / logout.php)
- [x] Form login dengan email + password
- [x] 2 role: Donatur & Penyelenggara
- [x] Validasi login dari DB
- [x] Session PHP (bukan localStorage)
- [x] Nama user tampil di header setelah login
- [x] Tombol login berubah menjadi logout + nama user
- [x] Auto redirect ke login jika akses halaman yang butuh auth
- [x] Support `?redirect=` parameter untuk deep-link
- [x] Registrasi akun baru

### Halaman Detail (detail.php)
- [x] Data diambil dari DB berdasarkan `?id=`
- [x] Progress bar animasi
- [x] Daftar 5 donatur terverifikasi terbaru
- [x] Tombol donasi hanya untuk donatur yang login
- [x] Banner login required jika belum login

### Halaman Donasi (donasi.php)
- [x] Guard wajib login sebagai Donatur
- [x] Ringkasan kampanye (judul, target, terkumpul) dari DB
- [x] Data donatur diisi otomatis dari session/DB
- [x] Quick amount buttons
- [x] Validasi nominal minimal Rp 10.000
- [x] Validasi bukti transfer wajib diupload
- [x] Format file: JPG, PNG, PDF — maks 5MB
- [x] **Bukti transfer disimpan di server** (`uploads/bukti/`)
- [x] DB hanya menyimpan nama file (bukan blob)
- [x] Status donasi = **PENDING** (belum langsung terhitung)
- [x] Dana terkumpul hanya bertambah setelah diverifikasi

### Kelola Kampanye (kelola_kampanye.php) — Penyelenggara
- [x] CRUD kampanye (Tambah, Edit, Hapus)
- [x] Kampanye dengan dana ≥ Rp 10.000 **tidak bisa dihapus**
- [x] Upload gambar kampanye ke server
- [x] Lihat daftar donatur per kampanye
- [x] Verifikasi/tolak bukti transfer
- [x] Jika **diterima** → `dana_terkumpul += nominal`
- [x] Jika **ditolak** → dana tidak bertambah
- [x] Tampil ringkasan: dana terkumpul & dana pending

### Riwayat Donasi (riwayat_donasi.php) — Donatur
- [x] Semua riwayat donasi user yang login
- [x] **[BONUS]** Ringkasan: Verified / Pending / Ditolak (nominal + jumlah)
- [x] **[BONUS]** Indikator visual: 🟢 hijau / 🟡 kuning / 🔴 merah
- [x] **[BONUS]** Riwayat lengkap dengan status tiap donasi

---

## 🗄️ Struktur Database

| Tabel | Keterangan |
|---|---|
| `penyelenggara` | Akun pengelola kampanye |
| `donatur` | Akun donatur |
| `kampanye` | Data kampanye crowdfunding |
| `donasi` | Riwayat donasi dengan status PENDING/verified/ditolak |

---

## 🔧 Perbaikan & Peningkatan (Revisi)

Perbaikan berikut dilakukan terhadap versi awal:

### Bug & Kebenaran Data
- **`sanitize()` tidak lagi double-escape.** Sebelumnya input dijalankan melalui `real_escape_string(htmlspecialchars(strip_tags()))` padahal seluruh query sudah memakai *prepared statement* dan output sudah di-escape. Akibatnya data rusak (mis. `O'Brien` → `O\'Brien`, `&` → `&amp;`). Sekarang hanya `trim(strip_tags())`.
- **Verifikasi donasi tidak lagi double-counting.** Mengklik / me-refresh link "Terima" lebih dari sekali sebelumnya menambah `dana_terkumpul` berulang. Kini ada penjaga `status='pending'` + cek `affected_rows`.
- **Kredensial demo diperbaiki** di halaman login (`donatur@gmail.com` → `budi@gmail.com`, akun yang benar-benar ada).
- **Placeholder gambar lokal.** `via.placeholder.com` sudah mati (2024); diganti SVG inline.
- **Ternary rapuh** di kelola (`... and $msgType=...`) ditulis ulang menjadi if/else yang jelas.
- Memperbaiki *notice* `$_POST['action']` yang belum di-set & URL paginasi yang berawalan `&`.

### Keamanan
- **Proteksi open-redirect** pada parameter `?redirect=` (hanya path lokal yang diizinkan).
- **Anti session-fixation**: `session_regenerate_id(true)` saat login/registrasi.
- **Token CSRF** pada semua aksi yang mengubah data (login, registrasi, donasi, tambah/edit/hapus kampanye, verifikasi/tolak donasi).
- **Logout menyeluruh**: membersihkan `$_SESSION`, cookie session, lalu `session_destroy()`.
- **Validasi upload diperketat**: cek tipe MIME asli via `finfo` (bukan sekadar ekstensi), nama file acak, penanganan error upload, serta `.htaccess` untuk mencegah eksekusi skrip di folder `uploads/`.

### UX
- Tombol donasi pada kampanye yang sudah lewat *deadline* dinonaktifkan dengan pesan jelas (sebelumnya pengguna ditendang ke beranda tanpa keterangan).
- Input formulir donasi (nominal, metode, pesan, anonim) dipertahankan bila terjadi error validasi.
- `rel="noopener"` pada link bukti transfer yang membuka tab baru.
