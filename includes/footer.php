</main>

<!-- Sticky Footer -->
<footer class="bg-white border-top position-sticky bottom-0 w-100" style="z-index: 1000;">
  <div class="container py-3 text-center text-muted small">
    © <?= date('Y') ?> <?= htmlspecialchars($brandName ?? 'Zino Auto') ?>. جميع الحقوق محفوظة.
  </div>
</footer>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

<!-- ملف JavaScript مخصص (اختياري) -->
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>