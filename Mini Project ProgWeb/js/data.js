// ============================================================
// DATA KAMPANYE - Aksi Nurani
// ============================================================
const CAMPAIGNS = [
  {
    id: 'cianjur',
    title: 'Bantuan Korban Gempa Bumi di Cianjur',
    organizer: 'Relawan Peduli Bencana',
    category: 'Bencana Alam',
    categoryClass: 'bencana',
    location: 'Cianjur, Jawa Barat',
    target: 100000000,
    collected: 45000000,
    deadline: '2026-05-10',
    deadlineText: '10 Mei 2026',
    image: 'https://indonesiaberbagi.org/assets/images/news/6025e2a0dbbab34e818880059b8a7337.JPG',
    description: 'Bencana gempa bumi yang terjadi di Cianjur telah menyebabkan banyak warga kehilangan tempat tinggal. Rumah-rumah rusak berat, fasilitas umum hancur, dan banyak keluarga kini hidup di tenda darurat.',
    description2: 'Melalui kampanye ini, kami mengajak Anda untuk membantu menyediakan kebutuhan dasar seperti makanan, air bersih, selimut, dan obat-obatan. Setiap bantuan Anda sangat berarti bagi mereka.',
    donorCount: 312
  },
  {
    id: 'papua',
    title: 'Beasiswa untuk Anak Papua',
    organizer: 'Yayasan Pendidikan Nusantara',
    category: 'Pendidikan',
    categoryClass: 'pendidikan',
    location: 'Jayapura, Papua',
    target: 75000000,
    collected: 25000000,
    deadline: '2026-05-20',
    deadlineText: '20 Mei 2026',
    image: 'https://greennetwork.id/wp-content/uploads/sites/2/2024/07/ANAK-PAPUA-SD-1024x504.webp',
    description: 'Banyak anak berprestasi di Papua yang terancam putus sekolah karena keterbatasan biaya. Kampanye ini bertujuan untuk memberikan beasiswa pendidikan penuh bagi mereka yang membutuhkan.',
    description2: 'Mari wujudkan generasi penerus bangsa yang cerdas dengan menyisihkan sebagian rezeki kita untuk membiayai sekolah mereka.',
    donorCount: 189
  },
  {
    id: 'rina',
    title: 'Bantuan Operasi Jantung Adik Rina',
    organizer: 'Keluarga Adik Rina',
    category: 'Kesehatan',
    categoryClass: 'kesehatan',
    location: 'Surabaya, Jawa Timur',
    target: 150000000,
    collected: 60000000,
    deadline: '2026-06-05',
    deadlineText: '5 Juni 2026',
    image: 'https://www.axa-mandiri.co.id/documents/1415637/44530363/Prosedur+Operasi+Jantung+Bocor.jpg/e2315565-ba51-bb8a-de51-c799d03c861f?t=1689559677510',
    description: 'Adik Rina yang baru berusia 5 tahun didiagnosa mengalami kebocoran jantung sejak lahir. Dokter menyarankan operasi segera agar kondisinya tidak memburuk.',
    description2: 'Biaya yang sangat besar menjadi kendala keluarga. Bantuan Anda akan langsung disalurkan ke pihak rumah sakit untuk keperluan operasi dan perawatan intensif.',
    donorCount: 423
  },
  {
    id: 'pohon',
    title: 'Gerakan Menanam 1000 Pohon',
    organizer: 'Komunitas Hijau Indonesia',
    category: 'Lingkungan',
    categoryClass: 'lingkungan',
    location: 'Bandung, Jawa Barat',
    target: 40000000,
    collected: 15000000,
    deadline: '2026-06-30',
    deadlineText: '30 Juni 2026',
    image: 'https://amalsholeh-s3.imgix.net/cover/oc8lKUj9OSwcS5com7kXfnm4UZVwjPmvwKFTFQwT.png',
    description: 'Kawasan hutan lindung di utara Bandung mulai gundul akibat pembalakan liar. Hal ini sangat berpotensi menyebabkan longsor dan banjir bandang di musim hujan.',
    description2: 'Komunitas kami menggalang dana untuk membeli bibit unggul dan membiayai operasional relawan untuk melakukan reboisasi massal.',
    donorCount: 97
  },
  {
    id: 'lansia',
    title: 'Bantuan Pangan untuk Lansia Terlantar',
    organizer: 'Aksi Sosial Nusantara',
    category: 'Sosial',
    categoryClass: 'sosial',
    location: 'Yogyakarta',
    target: 50000000,
    collected: 18000000,
    deadline: '2026-05-25',
    deadlineText: '25 Mei 2026',
    image: 'https://assets.bmm.or.id/uploads/campaigns/sedekah-harian-untuk-pangan-lansia-sebatangkara-1728898599.jpg',
    description: 'Masih banyak lansia sebatang kara yang harus berjuang keras hanya untuk mendapatkan sesuap nasi setiap harinya. Tubuh rentan mereka sering kali tak mampu lagi bekerja.',
    description2: 'Donasi ini akan dikonversi menjadi paket sembako bergizi yang akan didistribusikan rutin setiap bulan ke kantong-kantong pemukiman lansia dhuafa.',
    donorCount: 134
  }
];

// ============================================================
// USER AUTH (localStorage simulation)
// ============================================================
const Auth = {
  login(email, password, role) {
    const users = JSON.parse(localStorage.getItem('an_users') || '[]');
    const user = users.find(u => u.email === email && u.password === password && u.role === role);
    if (user) {
      localStorage.setItem('an_session', JSON.stringify({ email: user.email, name: user.name, role: user.role }));
      return { success: true, user };
    }
    return { success: false, message: 'Email, password, atau role tidak sesuai.' };
  },
  register(name, email, password, role) {
    const users = JSON.parse(localStorage.getItem('an_users') || '[]');
    if (users.find(u => u.email === email)) {
      return { success: false, message: 'Email sudah terdaftar.' };
    }
    users.push({ name, email, password, role });
    localStorage.setItem('an_users', JSON.stringify(users));
    localStorage.setItem('an_session', JSON.stringify({ email, name, role }));
    return { success: true };
  },
  logout() {
    localStorage.removeItem('an_session');
  },
  getSession() {
    const s = localStorage.getItem('an_session');
    return s ? JSON.parse(s) : null;
  },
  isLoggedIn() {
    return !!this.getSession();
  }
};

// ============================================================
// DONATION DATA
// ============================================================
const DonationDB = {
  getAll() {
    return JSON.parse(localStorage.getItem('an_donations') || '[]');
  },
  add(donation) {
    const donations = this.getAll();
    donation.id = Date.now();
    donation.createdAt = new Date().toISOString();
    donations.push(donation);
    localStorage.setItem('an_donations', JSON.stringify(donations));
    return donation;
  },
  getTotalForCampaign(campaignId) {
    return this.getAll()
      .filter(d => d.campaignId === campaignId)
      .reduce((sum, d) => sum + Number(d.nominal), 0);
  }
};

// ============================================================
// UTILS
// ============================================================
function formatRupiah(num) {
  return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function getProgress(collected, target) {
  return Math.min(Math.round((collected / target) * 100), 100);
}

function getCampaignById(id) {
  return CAMPAIGNS.find(c => c.id === id);
}

function updateNavAuth() {
  const session = Auth.getSession();
  const navAuth = document.getElementById('nav-auth');
  if (!navAuth) return;
  if (session) {
    navAuth.innerHTML = `
      <span class="nav-username">👤 ${session.name}</span>
      <a href="#" class="nav-link btn-logout" onclick="Auth.logout(); window.location.reload();">Keluar</a>
    `;
  } else {
    navAuth.innerHTML = `<a href="login.html" class="nav-link btn-login">Login</a>`;
  }
}