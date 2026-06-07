<footer class="main-footer">
  <p>&copy; 2026 <strong>Aksi Nurani</strong> — Platform Donasi Terpercaya &nbsp;|&nbsp; 71241095 | 71241114 | 71241116</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {

  var fadeItems = document.querySelectorAll('.fade-in');
  fadeItems.forEach(function(item, index) {
    item.style.transitionDelay = (index * 0.08) + 's';
  });

  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  fadeItems.forEach(function(item) {
    observer.observe(item);
  });

  document.querySelectorAll('[data-width]').forEach(function(el) {
    var targetWidth = el.dataset.width;
    el.style.width = '0';
    requestAnimationFrame(function() {
      requestAnimationFrame(function() {
        el.style.width = targetWidth + '%';
      });
    });
  });

});
</script>