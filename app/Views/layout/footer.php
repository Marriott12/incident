</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($show_map)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mgrs@1.0.0/dist/mgrs.min.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
<script src="<?= htmlspecialchars($base_path ?? '') ?>/js/map.js"></script>
<script src="<?= htmlspecialchars($base_path ?? '') ?>/js/dashboard-map.js"></script>
<?php endif; ?>
</body>
</html>
