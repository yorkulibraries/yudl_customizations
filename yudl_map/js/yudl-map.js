(function (Drupal, drupalSettings) {
  Drupal.behaviors.yudlMap = {
    attach: function (context, settings) {
      const mapEl = document.getElementById('map');
      if (!mapEl) return;

      // Only initialize once.
      if (mapEl._leaflet_initialized) return;
      mapEl._leaflet_initialized = true;

      // Start map centered on a better view.
      const map = L.map('map', {
        fullscreenControl: true,
        zoomControl: false,
      }).setView([30, 0], 3);

      // Add standard zoom control at top-left
      L.control.zoom({ position: 'topright' }).addTo(map);

      // Optional: add scale bar
      L.control.scale({ position: 'bottomright', metric: true, imperial: false }).addTo(map);

      // Add tile layer
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      // MarkerClusterGroup for clustering
      const markers = L.markerClusterGroup();

      fetch('/explore/map/data')
        .then(res => res.json())
        .then(data => {
          data.forEach(node => {
            if (!node.lat || !node.lng) return;
            const marker = L.marker([parseFloat(node.lat), parseFloat(node.lng)]);
            marker.bindPopup(`<a href="${node.url}">${node.title}</a>`);
            markers.addLayer(marker);
          });
          map.addLayer(markers);

          // Fit map to marker bounds if markers exist
          if (markers.getLayers().length > 0) {
            map.fitBounds(markers.getBounds(), { padding: [50, 50] });
          }
        });
    }
  };
})(Drupal, drupalSettings);
