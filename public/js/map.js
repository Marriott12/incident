// Map logic for form map and dashboard map
(function () {
	console.log('map.js loaded');
	const basePath = document.body.dataset.basePath || '';

	// Utility: threat color
	function threatColor(level) {
		switch (level) {
			case 'moderate': return '#EF9F27';
			case 'high': return '#D85A30';
			case 'critical': return '#E24B4A';
			default: return '#1D9E75';
		}
	}

	// FORM MAP
	const formMapEl = document.getElementById('form-map');
	if (formMapEl) {
		const gridInput = document.getElementById('grid_reference');
		const latInput = document.getElementById('latitude');
		const lngInput = document.getElementById('longitude');
		const map = L.map(formMapEl).setView([-14.0, 27.8], 7);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; OpenStreetMap contributors'
		}).addTo(map);

		function setCoordinateInputs(latlng) {
			if (!latInput || !lngInput) return;
			latInput.value = latlng.lat.toFixed(8);
			lngInput.value = latlng.lng.toFixed(8);
		}

		function setGridReference(latlng) {
			if (!gridInput || typeof mgrs === 'undefined') return;
			try {
				gridInput.value = mgrs.forward([latlng.lng, latlng.lat], 5);
			} catch (error) {
				console.warn('Unable to derive grid reference', error);
			}
		}

		function updateLocationFields(latlng, options) {
			setCoordinateInputs(latlng);
			if (!options || options.updateGrid !== false) {
				setGridReference(latlng);
			}
		}

		let marker = null;
		function ensureMarker(latlng, options) {
			if (!marker) {
				marker = L.marker(latlng, { draggable: true }).addTo(map);
				marker.on('dragend', function (ev) {
					const ll = ev.target.getLatLng();
					updateLocationFields(ll);
				});
			} else {
				marker.setLatLng(latlng);
			}
			if (!options || options.center !== false) {
				map.flyTo(latlng, Math.max(map.getZoom(), 13), { duration: 0.6 });
			}
			updateLocationFields(latlng, options);
		}

		// load AO sectors
		fetch(`${basePath}/api/sectors`).then(r => r.json()).then(j => {
			if (j && j.features) {
				L.geoJSON(j, { style: { color: '#007bff', weight: 2, fillOpacity: 0.1 } }).addTo(map);
			}
		}).catch(() => {});

		map.on('click', function (e) {
			ensureMarker(e.latlng, { center: false });
		});

		if (gridInput) {
			gridInput.addEventListener('keydown', function (event) {
				if (event.key !== 'Enter') return;
				event.preventDefault();
				if (typeof mgrs === 'undefined') return;
				const value = gridInput.value.trim();
				if (!value) return;
				try {
					const point = mgrs.toPoint(value.toUpperCase());
					ensureMarker(L.latLng(point[1], point[0]));
				} catch (error) {
					console.warn('Invalid grid reference', error);
				}
			});
		}

		const initialLat = latInput && latInput.value ? Number(latInput.value) : null;
		const initialLng = lngInput && lngInput.value ? Number(lngInput.value) : null;
		if (Number.isFinite(initialLat) && Number.isFinite(initialLng)) {
			ensureMarker(L.latLng(initialLat, initialLng), { updateGrid: !gridInput || !gridInput.value });
		} else if (gridInput && gridInput.value && typeof mgrs !== 'undefined') {
			try {
				const point = mgrs.toPoint(gridInput.value.trim().toUpperCase());
				ensureMarker(L.latLng(point[1], point[0]), { updateGrid: false });
			} catch (error) {
				console.warn('Unable to initialize grid reference', error);
			}
		}

		// Draw control for AO polygon
		const drawnItems = new L.FeatureGroup();
		map.addLayer(drawnItems);
		const drawControl = new L.Control.Draw({
			edit: { featureGroup: drawnItems },
			draw: { polyline: false, rectangle: false, circle: false, marker: false, circlemarker: false }
		});
		map.addControl(drawControl);

		map.on(L.Draw.Event.CREATED, function (event) {
			const layer = event.layer;
			drawnItems.clearLayers();
			drawnItems.addLayer(layer);
			const gj = layer.toGeoJSON();
			document.getElementById('ao_polygon').value = JSON.stringify(gj.geometry);
		});

		map.on(L.Draw.Event.EDITED, function (e) {
			const layers = e.layers;
			layers.eachLayer(function (layer) {
				document.getElementById('ao_polygon').value = JSON.stringify(layer.toGeoJSON().geometry);
			});
		});
	}

	// DASHBOARD MAP
	const dashEl = document.getElementById('dashboard-map');
	if (dashEl) {
		const map = L.map(dashEl).setView([-14.0, 27.8], 7);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

		// load sectors
		fetch(`${basePath}/api/sectors`).then(r => r.json()).then(j => {
			if (j && j.features) L.geoJSON(j, { style: { color: '#007bff', weight: 2, fillOpacity: 0.05 } }).addTo(map);
		}).catch(() => {});

		// load incidents as GeoJSON
		fetch(`${basePath}/api/incidents/geojson`).then(r => r.json()).then(data => {
			if (!data || !data.features) return;
			const markers = L.markerClusterGroup ? L.markerClusterGroup() : L.layerGroup();
			data.features.forEach(f => {
				if (!f.geometry) return;
				const coords = f.geometry.coordinates;
				const props = f.properties || {};
				const color = threatColor(props.threat_level);
				const icon = L.circleMarker([coords[1], coords[0]], { radius: 8, fillColor: color, color: '#000', weight: 1, fillOpacity: 0.9 });
				const popup = `<strong>${props.incident_number}</strong><br>Type: ${props.type}<br>Status: ${props.status}<br><a href="${basePath}/incidents/${props.id}">View</a>`;
				icon.bindPopup(popup);
				if (markers.addLayer) markers.addLayer(icon);
				else markers.addLayer(icon);
			});
			markers.addTo(map);
		}).catch(() => {});
	}

	// INCIDENT DETAIL MAP
	const detailEl = document.getElementById('incident-detail-map');
	if (detailEl) {
		function initIncidentDetailMap() {
			if (detailEl.dataset.mapInitialized === '1') return;
			if (typeof L === 'undefined') {
				console.warn('Leaflet not available for incident detail map');
				return;
			}

			const threat = detailEl.dataset.threat || 'low';
			const status = detailEl.dataset.status || 'open';
			const incidentNo = detailEl.dataset.incidentNumber || 'Incident';
			const color = threatColor(threat);
			const gridReference = (detailEl.dataset.gridReference || '').trim();

			let lat = parseFloat(detailEl.dataset.lat || '');
			let lng = parseFloat(detailEl.dataset.lng || '');
			if ((!Number.isFinite(lat) || !Number.isFinite(lng)) && gridReference !== '' && typeof mgrs !== 'undefined') {
				try {
					const point = mgrs.toPoint(gridReference.toUpperCase());
					lng = point[0];
					lat = point[1];
				} catch (error) {
					console.warn('Could not parse grid reference for detail map', error);
				}
			}

			const hasPoint = Number.isFinite(lat) && Number.isFinite(lng);
			const center = hasPoint ? [lat, lng] : [-15.4167, 28.2833];
			const zoom = hasPoint ? 13 : 7;

			const map = L.map(detailEl).setView(center, zoom);
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '&copy; OpenStreetMap contributors'
			}).addTo(map);

			if (hasPoint) {
                const intensity = threat === 'critical' ? 1.0 : threat === 'high' ? 0.95 : threat === 'moderate' ? 0.7 : 0.45;
                if (typeof L.heatLayer === 'function') {
                    L.heatLayer([[lat, lng, intensity]], {
                        radius: 70,
                        blur: 55,
                        maxZoom: 12,
                        minOpacity: 0.35,
                        gradient: {
                            0.2: '#198754',
                            0.4: '#0dcaf0',
                            0.6: '#ffc107',
                            0.8: '#fd7e14',
                            1.0: '#dc3545'
                        }
                    }).addTo(map);
                }

                const marker = L.circleMarker([lat, lng], {
                    radius: 10,
                    fillColor: color,
                    color: '#111111',
                    weight: 1,
                    fillOpacity: 0.95
                }).addTo(map);
                marker.bindPopup(`<strong>${incidentNo}</strong><br>Threat: ${threat}<br>Status: ${status}`).openPopup();
            } else {
				L.popup({ closeButton: false, autoClose: false, closeOnClick: false })
					.setLatLng(center)
					.setContent('<strong>No precise coordinates saved</strong><br>Use grid reference or map pin during incident capture.')
					.openOn(map);
			}

			detailEl.dataset.mapInitialized = '1';
			setTimeout(function () { map.invalidateSize(); }, 50);
			window.addEventListener('resize', function () { map.invalidateSize(); });
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initIncidentDetailMap, { once: true });
		} else {
			initIncidentDetailMap();
		}
		setTimeout(initIncidentDetailMap, 150);
	}

})();
