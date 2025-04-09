</div> <!-- .container -->

<footer class="text-center mt-5 text-muted small">
  <hr>
  <p>&copy; <?= date('Y'); ?> ComicNest</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

<!-- Dark Mode + Live Preview Scripts -->
<script>
function toggleDarkMode() {
  const body = document.body;
  const nav = document.querySelector('nav');
  const isDark = body.classList.toggle('bg-dark');

  body.classList.toggle('text-light');
  nav.classList.toggle('navbar-dark');
  nav.classList.toggle('bg-dark');
  nav.classList.toggle('navbar-light');
  nav.classList.toggle('bg-light');

  document.cookie = `dark_mode=${isDark};path=/;max-age=31536000`;
}

function previewCover(url) {
  const preview = document.getElementById('cover_preview');
  preview.innerHTML = '';

  if (!url.trim()) return;

  const img = document.createElement('img');
  img.src = url;
  img.alt = 'Cover Preview';
  img.style.maxHeight = '200px';
  img.style.borderRadius = '0.5rem';
  img.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
  img.style.opacity = '0';
  img.style.transition = 'opacity 0.4s ease';

  img.onload = () => {
    preview.appendChild(img);
    setTimeout(() => img.style.opacity = '1', 50);
  };

  img.onerror = () => {
    const msg = document.createElement('p');
    msg.className = "text-muted mt-2";
    msg.innerText = "⚠️ Could not load image preview.";
    preview.appendChild(msg);
  };
}
</script>

<script>
  // Apply stored theme on load
  document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('comicnest-theme') === 'dark') {
      document.body.classList.add('dark-mode');
    }

    const toggle = document.getElementById('themeToggle');
    if (toggle) {
      toggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('comicnest-theme', isDark ? 'dark' : 'light');
      });
    }
  });
</script>

</body>
</html>
