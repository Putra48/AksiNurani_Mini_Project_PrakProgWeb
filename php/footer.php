<?php
// ============================================================
// KOMPONEN FOOTER — di-include oleh semua halaman
// ============================================================
// Cara pakai:
//   <?php include 'php/footer.php'; ?>
//
// Footer ini juga berisi script global:
//   1. Scroll Reveal Animation (class "fade-in")
//   2. Progress Bar Animation (atribut "data-width")
// ============================================================
?>
<footer class="main-footer">
  <p>&copy; 2026 <strong>Aksi Nurani</strong> — Platform Donasi Terpercaya &nbsp;|&nbsp; Dibuat dengan ❤️ untuk Indonesia</p>
</footer>

<!-- ========================================== -->
<!-- Script Global: Scroll Reveal & Progress Bar -->
<!-- ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {

  // -------------------------------------------------
  // 1. SCROLL REVEAL ANIMATION
  //    Elemen dengan class "fade-in" akan muncul
  //    perlahan saat di-scroll ke viewport.
  // -------------------------------------------------
  var fadeItems = document.querySelectorAll('.fade-in');

  // Beri delay bertingkat → efek muncul satu per satu
  fadeItems.forEach(function(item, index) {
    item.style.transitionDelay = (index * 0.08) + 's';
  });

  // IntersectionObserver = deteksi elemen masuk layar
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');   // Tambah class → animasi jalan
        observer.unobserve(entry.target);        // Stop observasi (cukup 1x)
      }
    });
  }, { threshold: 0.1 });  // Muncul saat 10% terlihat

  fadeItems.forEach(function(item) {
    observer.observe(item);
  });

  // -------------------------------------------------
  // 2. PROGRESS BAR ANIMATION
  //    Elemen dengan atribut data-width="70"
  //    akan mengisi dari 0% ke 70% secara smooth.
  // -------------------------------------------------
  document.querySelectorAll('[data-width]').forEach(function(el) {
    var targetWidth = el.dataset.width;
    el.style.width = '0';
    // Double requestAnimationFrame = pastikan browser sempat render 0% dulu
    requestAnimationFrame(function() {
      requestAnimationFrame(function() {
        el.style.width = targetWidth + '%';
      });
    });
  });

});
</script>
