-- ============================================================
-- DATABASE: Aksi Nurani - Platform Donasi
-- ============================================================

CREATE DATABASE IF NOT EXISTS aksi_nurani CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aksi_nurani;

-- ============================================================
-- TABEL PENYELENGGARA
-- ============================================================
CREATE TABLE IF NOT EXISTS penyelenggara (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kantor VARCHAR(200) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABEL DONATUR
-- ============================================================
CREATE TABLE IF NOT EXISTS donatur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    no_telepon VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABEL KAMPANYE
-- ============================================================
CREATE TABLE IF NOT EXISTS kampanye (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penyelenggara_id INT NOT NULL,
    judul VARCHAR(300) NOT NULL,
    kategori ENUM('Bencana Alam','Pendidikan','Kesehatan','Lingkungan','Sosial') NOT NULL,
    lokasi VARCHAR(200) NOT NULL,
    deskripsi TEXT NOT NULL,
    deskripsi2 TEXT,
    target_dana DECIMAL(15,2) NOT NULL,
    dana_terkumpul DECIMAL(15,2) DEFAULT 0,
    gambar VARCHAR(300),
    rekening_info TEXT,
    deadline DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (penyelenggara_id) REFERENCES penyelenggara(id) ON DELETE CASCADE
);

-- ============================================================
-- TABEL DONASI
-- ============================================================
CREATE TABLE IF NOT EXISTS donasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kampanye_id INT NOT NULL,
    donatur_id INT NOT NULL,
    nama_donatur VARCHAR(150) NOT NULL,
    email_donatur VARCHAR(150) NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    metode_pembayaran VARCHAR(100) NOT NULL,
    pesan TEXT,
    bukti_transfer VARCHAR(300),
    status ENUM('pending','verified','ditolak') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (kampanye_id) REFERENCES kampanye(id) ON DELETE CASCADE,
    FOREIGN KEY (donatur_id) REFERENCES donatur(id) ON DELETE CASCADE
);

-- ============================================================
-- DATA AWAL: Penyelenggara (password: admin123)
-- ============================================================
INSERT INTO penyelenggara (nama_kantor, email, password, no_telepon, alamat) VALUES
('Relawan Peduli Bencana', 'relawan@aksinurani.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08111111111', 'Jl. Kemanusiaan No.1, Jakarta'),
('Yayasan Pendidikan Nusantara', 'yayasan@aksinurani.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08222222222', 'Jl. Cendekia No.5, Bandung'),
('Keluarga Adik Rina', 'rina@aksinurani.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08333333333', 'Jl. Sehat No.10, Surabaya'),
('Komunitas Hijau Indonesia', 'hijau@aksinurani.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08444444444', 'Jl. Lestari No.3, Bandung'),
('Aksi Sosial Nusantara', 'sosial@aksinurani.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08555555555', 'Jl. Bakti No.7, Yogyakarta');

-- ============================================================
-- DATA AWAL: Donatur (password: donatur123)
-- ============================================================
INSERT INTO donatur (nama, email, password, no_telepon) VALUES
('Budi Santoso', 'budi@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08123456789'),
('Siti Aminah', 'siti@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08987654321'),
('Ahmad Fauzi', 'ahmad@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08567891234');

-- ============================================================
-- DATA AWAL: Kampanye
-- ============================================================
INSERT INTO kampanye (penyelenggara_id, judul, kategori, lokasi, deskripsi, deskripsi2, target_dana, dana_terkumpul, gambar, rekening_info, deadline) VALUES
(1, 'Bantuan Korban Gempa Bumi di Cianjur', 'Bencana Alam', 'Cianjur, Jawa Barat',
 'Bencana gempa bumi yang terjadi di Cianjur telah menyebabkan banyak warga kehilangan tempat tinggal. Rumah-rumah rusak berat, fasilitas umum hancur, dan banyak keluarga kini hidup di tenda darurat.',
 'Melalui kampanye ini, kami mengajak Anda untuk membantu menyediakan kebutuhan dasar seperti makanan, air bersih, selimut, dan obat-obatan. Setiap bantuan Anda sangat berarti bagi mereka.',
 100000000, 45000000,
 'https://indonesiaberbagi.org/assets/images/news/6025e2a0dbbab34e818880059b8a7337.JPG',
 'BCA: 1234-5678-90|Mandiri: 0987-6543-21|GoPay/DANA: 0812-3456-7890',
 '2026-08-10'),
(2, 'Beasiswa untuk Anak Papua', 'Pendidikan', 'Jayapura, Papua',
 'Banyak anak berprestasi di Papua yang terancam putus sekolah karena keterbatasan biaya. Kampanye ini bertujuan untuk memberikan beasiswa pendidikan penuh bagi mereka yang membutuhkan.',
 'Mari wujudkan generasi penerus bangsa yang cerdas dengan menyisihkan sebagian rezeki kita untuk membiayai sekolah mereka.',
 75000000, 25000000,
 'https://greennetwork.id/wp-content/uploads/sites/2/2024/07/ANAK-PAPUA-SD-1024x504.webp',
 'BCA: 1234-5678-90|Mandiri: 0987-6543-21|GoPay/DANA: 0812-3456-7890',
 '2026-08-20'),
(3, 'Bantuan Operasi Jantung Adik Rina', 'Kesehatan', 'Surabaya, Jawa Timur',
 'Adik Rina yang baru berusia 5 tahun didiagnosa mengalami kebocoran jantung sejak lahir. Dokter menyarankan operasi segera agar kondisinya tidak memburuk.',
 'Biaya yang sangat besar menjadi kendala keluarga. Bantuan Anda akan langsung disalurkan ke pihak rumah sakit untuk keperluan operasi dan perawatan intensif.',
 150000000, 60000000,
 'https://www.axa-mandiri.co.id/documents/1415637/44530363/Prosedur+Operasi+Jantung+Bocor.jpg/e2315565-ba51-bb8a-de51-c799d03c861f?t=1689559677510',
 'BCA: 1234-5678-90|Mandiri: 0987-6543-21|GoPay/DANA: 0812-3456-7890',
 '2026-09-05'),
(4, 'Gerakan Menanam 1000 Pohon', 'Lingkungan', 'Bandung, Jawa Barat',
 'Kawasan hutan lindung di utara Bandung mulai gundul akibat pembalakan liar. Hal ini sangat berpotensi menyebabkan longsor dan banjir bandang di musim hujan.',
 'Komunitas kami menggalang dana untuk membeli bibit unggul dan membiayai operasional relawan untuk melakukan reboisasi massal.',
 40000000, 15000000,
 'https://amalsholeh-s3.imgix.net/cover/oc8lKUj9OSwcS5com7kXfnm4UZVwjPmvwKFTFQwT.png',
 'BCA: 1234-5678-90|Mandiri: 0987-6543-21|GoPay/DANA: 0812-3456-7890',
 '2026-09-30'),
(5, 'Bantuan Pangan untuk Lansia Terlantar', 'Sosial', 'Yogyakarta',
 'Masih banyak lansia sebatang kara yang harus berjuang keras hanya untuk mendapatkan sesuap nasi setiap harinya. Tubuh rentan mereka sering kali tak mampu lagi bekerja.',
 'Donasi ini akan dikonversi menjadi paket sembako bergizi yang akan didistribusikan rutin setiap bulan ke kantong-kantong pemukiman lansia dhuafa.',
 50000000, 18000000,
 'https://assets.bmm.or.id/uploads/campaigns/sedekah-harian-untuk-pangan-lansia-sebatangkara-1728898599.jpg',
 'BCA: 1234-5678-90|Mandiri: 0987-6543-21|GoPay/DANA: 0812-3456-7890',
 '2026-08-25');

-- ============================================================
-- DATA AWAL: Donasi sample
-- ============================================================
INSERT INTO donasi (kampanye_id, donatur_id, nama_donatur, email_donatur, nominal, metode_pembayaran, pesan, bukti_transfer, status) VALUES
(1, 1, 'Budi Santoso', 'budi@gmail.com', 500000, 'Transfer Bank BCA', 'Semoga lekas pulih', 'sample.jpg', 'verified'),
(1, 2, 'Siti Aminah', 'siti@gmail.com', 250000, 'E-Wallet GoPay', 'Semangat!', 'sample2.jpg', 'verified'),
(2, 1, 'Budi Santoso', 'budi@gmail.com', 150000, 'Transfer Bank Mandiri', 'Untuk generasi penerus', 'sample3.jpg', 'pending'),
(3, 3, 'Ahmad Fauzi', 'ahmad@gmail.com', 1000000, 'Transfer Bank BCA', 'Semoga cepat sembuh ya dik', 'sample4.jpg', 'verified');
