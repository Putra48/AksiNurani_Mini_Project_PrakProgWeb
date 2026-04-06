#  Aksi Nurani - Sistem Crowdfunding Sosial

**Mini Project #1 - Praktikum Pemrograman Web**

Proyek ini adalah prototipe *front-end* website crowdfunding (penggalangan dana) statis yang difokuskan pada kegiatan sosial seperti bantuan bencana alam, pendidikan, kesehatan, dan lingkungan. 

Situs ini dibangun secara **murni menggunakan HTML5 dan CSS3** tanpa bantuan *framework* (seperti Bootstrap/Tailwind) maupun JavaScript, guna memenuhi kriteria penilaian Mini Project #1.

##  Struktur Halaman (4 Halaman Utama)

1. **Halaman Utama (`index.html`)**
   * Menampilkan daftar kampanye sosial dalam bentuk *Card UI* yang modern.
   * Dilengkapi dengan *mockup* area pencarian dan filter (Kategori, Lokasi, Target Dana) yang siap dikembangkan di tahap dinamis.
   * Terdapat label/badge kategori pada setiap gambar kampanye.

2. **Halaman Detail Kampanye (`detail.html`)**
   * Menerapkan layout 2 kolom (Flexbox) yang memisahkan poster kampanye dan detail teks.
   * Menampilkan 11 informasi wajib kampanye secara terstruktur.
   * Dilengkapi komponen *Progress Bar* (dibuat murni dengan CSS) dan rincian metode pembayaran/informasi rekening.

3. **Halaman Pengajuan Donasi (`donasi.html`)**
   * Memiliki form input data diri yang komprehensif, pilihan nominal, dan metode pembayaran.
   * Terdapat fitur unggah file (`<input type="file">`) untuk Bukti Transfer.
   * Menampilkan Pop-Up / Modal "Donasi Berhasil" murni menggunakan trik CSS (`:target`), tanpa intervensi JavaScript.

4. **Halaman Login (`login.html`)**
   * Antarmuka login terpusat (*centered layout*).
   * Dilengkapi fitur pemilihan peran (Donatur / Pengelola Kampanye) dan input `type="password"` demi keamanan dasar.

##  Kriteria & Teknologi yang Diterapkan

* **Pure HTML & CSS:** Sama sekali tidak menggunakan CSS Framework, template, maupun *scripting logic*.
* **CSS Eksternal:** Seluruh *styling* dipisah ke dalam folder `/css` (`global.css`, `index.css`, `detail.css`, `donasi.css`, `login.css`) dan dipanggil menggunakan tag `<link>`.
* **Navigasi Sempurna:** Terdapat *Hyperlink* lengkap yang saling menghubungkan keempat halaman, termasuk tombol kembali ("Back") untuk navigasi pengguna.
* **Layouting Modern:** Penggunaan `display: flex` untuk penataan letak yang responsif dan konsisten, serta efek visual interaktif (seperti *hover state* dan *box-shadow*).



## penyusun

* Philip Luis Nurcahyo | 71241095
* Hendrikus Lanang Ona | 71231114
* Putra Eka Setiawan   | 71241116
