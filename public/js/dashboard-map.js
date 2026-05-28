// Dashboard geographic visualization
(function () {
    const basePath = document.body.dataset.basePath || '';
    
    // Initialize dashboard map with heatmap
    const dashboardMapEl = document.getElementById('dashboard-incident-map');
    if (!dashboardMapEl) return;
    
    const map = L.map(dashboardMapEl).setView([-14.0, 27.8], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const province = (dashboardMapEl.dataset.province || '').trim();
    const normalizeProvince = (name) => {
        return name
            .toLowerCase()
            .replace(/province$/i, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();
    };

    const provinceViews = {
        lusaka: { center: [-15.4067, 28.2871], zoom: 8 },
        copperbelt: { center: [-13.1339, 27.8490], zoom: 8 },
        northern: { center: [-11.9657, 29.6229], zoom: 7 },
        muchinga: { center: [-11.4994, 31.3908], zoom: 7 },
        southern: { center: [-16.2365, 26.0693], zoom: 7 },
        eastern: { center: [-13.1500, 31.5167], zoom: 7 },
        western: { center: [-15.5656, 23.1043], zoom: 7 },
        'north western': { center: [-12.8000, 25.4000], zoom: 7 },
        luapula: { center: [-11.7769, 29.6403], zoom: 7 },
        central: { center: [-14.2673, 27.6726], zoom: 7 }
    };
    const zambiaBounds = [[-17.8, 25.0], [-8.0, 33.7]];
    let provinceZoomApplied = false;

    if (province) {
        const key = normalizeProvince(province);
        const view = provinceViews[key];
        if (view) {
            map.setView(view.center, view.zoom);
            provinceZoomApplied = true;
        }
    }

    // Fetch incident GeoJSON
    fetch(`${basePath}/api/incidents/geojson`)
        .then(r => r.json())
        .then(data => {
            if (!data.features || !Array.isArray(data.features)) return;
            
            // Create heatmap layer
            const heatData = data.features
                .filter(f => f.geometry && f.geometry.type === 'Point' && f.geometry.coordinates)
                .map(f => {
                    const coords = f.geometry.coordinates;
                    const threat = f.properties?.threat_level || 'low';
                    const intensity = threat === 'critical' ? 1.0 : 
                                    threat === 'high' ? 0.75 :
                                    threat === 'moderate' ? 0.5 : 0.25;
                    return [coords[1], coords[0], intensity];
                });

            if (heatData.length > 0) {
                L.heatLayer(heatData, {
                    radius: 25,
                    blur: 15,
                    maxZoom: 1,
                    gradient: {0.2: '#198754', 0.4: '#0dcaf0', 0.6: '#ffc107', 0.8: '#fd7e14', 1.0: '#dc3545'}
                }).addTo(map);
            }

            // Add cluster markers by threat level
            const threatClusters = {
                critical: L.featureGroup(),
                high: L.featureGroup(),
                moderate: L.featureGroup(),
                low: L.featureGroup()
            };

            data.features.forEach(f => {
                if (!f.geometry || f.geometry.type !== 'Point') return;
                const coords = f.geometry.coordinates;
                const threat = f.properties?.threat_level || 'low';
                const incidentNo = f.properties?.incident_number || 'Unknown';
                const status = f.properties?.status || 'open';
                const type = f.properties?.type || 'unknown';

                const threatColorMap = {
                    critical: '#dc3545',
                    high: '#fd7e14',
                    moderate: '#0dcaf0',
                    low: '#198754'
                };

                const marker = L.circleMarker([coords[1], coords[0]], {
                    radius: threat === 'critical' ? 8 : threat === 'high' ? 6 : 5,
                    fillColor: threatColorMap[threat] || '#6c757d',
                    color: '#fff',
                    weight: 2,
                    opacity: 0.8,
                    fillOpacity: 0.7
                }).bindPopup(`
                    <div style="font-size:0.85rem;width:200px;">
                        <strong>Incident #${incidentNo}</strong><br/>
                        Type: ${type}<br/>
                        Status: <span class="badge bg-${status === 'closed' ? 'secondary' : 'primary'}">${status}</span><br/>
                        Threat: <span class="badge bg-${threatColorMap[threat] === '#dc3545' ? 'danger' : threatColorMap[threat] === '#fd7e14' ? 'warning' : threatColorMap[threat] === '#0dcaf0' ? 'info' : 'success'}">${threat}</span>
                    </div>
                `);

                if (threatClusters[threat]) {
                    threatClusters[threat].addLayer(marker);
                }
            });

            // Add layer control
            const layerControl = L.control.layers(
                {},
                {
                    'Critical Threats': threatClusters.critical,
                    'High Threats': threatClusters.high,
                    'Moderate Threats': threatClusters.moderate,
                    'Low Threats': threatClusters.low
                },
                { position: 'topright', collapsed: false }
            ).addTo(map);

            // Show all layers by default
            Object.values(threatClusters).forEach(cluster => cluster.addTo(map));

            // Fit bounds if markers exist and no province-focused view has already been applied
            const allMarkers = L.featureGroup([
                threatClusters.critical,
                threatClusters.high,
                threatClusters.moderate,
                threatClusters.low
            ]);
            if (allMarkers.getLayers().length > 0 && !provinceZoomApplied) {
                map.fitBounds(allMarkers.getBounds().pad(0.1));
            }
        })
        .catch(err => console.error('Failed to load incident map data:', err));

    // Fit map to Zambia bounds on initial load only if no province has been selected
    if (!provinceZoomApplied) {
        map.fitBounds(zambiaBounds);
    }
})();
