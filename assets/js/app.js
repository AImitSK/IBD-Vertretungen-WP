/**
 * IBD Vertretungen - Main Application
 *
 * @package IBD_Vertretungen
 */

(function() {
    'use strict';

    // Configuration
    const config = window.ibdVertretungen || {
        mapCenter: { lat: 50, lng: 10 },
        mapZoom: 4,
        primaryColor: '#BE1622',
        restUrl: '/wp-json/ibd/v1/',
        i18n: {}
    };

    // State
    let map = null;
    let markers = [];
    let activeCard = null;
    let activeMarker = null;
    let infoWindow = null;
    let countryLayers = {};
    let vertretungenData = [];

    /**
     * Initialize the application
     */
    function init() {
        // Parse embedded data
        const dataElement = document.getElementById('ibd-vertretungen-data');
        if (dataElement) {
            try {
                vertretungenData = JSON.parse(dataElement.textContent);
            } catch (e) {
                console.error('Failed to parse vertretungen data:', e);
            }
        }

        // Initialize components
        initMap();
        initSearch();
        initCards();
        initAccordions();

        // Update results count
        updateResultsCount(vertretungenData.length);
    }

    /**
     * Initialize Google Map
     */
    function initMap() {
        const mapElement = document.getElementById('ibd-map');
        if (!mapElement || typeof google === 'undefined') {
            console.warn('Google Maps not available');
            return;
        }

        // Create map
        map = new google.maps.Map(mapElement, {
            center: config.mapCenter,
            zoom: config.mapZoom,
            styles: getMapStyles(),
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            }
        });

        // Create info window
        infoWindow = new google.maps.InfoWindow();

        // Add markers
        vertretungenData.forEach(v => {
            if (v.coordinates && v.coordinates.lat && v.coordinates.lng) {
                addMarker(v);
            }
        });

        // Hide loading overlay
        const loadingElement = document.getElementById('ibd-map-loading');
        if (loadingElement) {
            loadingElement.classList.add('hidden');
        }

        // Fit bounds if we have markers
        if (markers.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            markers.forEach(m => bounds.extend(m.getPosition()));
            map.fitBounds(bounds, { padding: 50 });
        }
    }

    /**
     * Get custom map styles
     */
    function getMapStyles() {
        return [
            {
                featureType: 'administrative',
                elementType: 'geometry.stroke',
                stylers: [{ color: '#c9c9c9' }]
            },
            {
                featureType: 'administrative.country',
                elementType: 'geometry.stroke',
                stylers: [{ color: '#a0a0a0' }]
            },
            {
                featureType: 'water',
                elementType: 'geometry.fill',
                stylers: [{ color: '#e3e8ee' }]
            },
            {
                featureType: 'landscape',
                elementType: 'geometry.fill',
                stylers: [{ color: '#f5f5f5' }]
            },
            {
                featureType: 'poi',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'transit',
                stylers: [{ visibility: 'off' }]
            }
        ];
    }

    /**
     * Add a marker to the map
     */
    function addMarker(vertretung) {
        const position = {
            lat: parseFloat(vertretung.coordinates.lat),
            lng: parseFloat(vertretung.coordinates.lng)
        };

        // Create custom marker icon
        const markerIcon = {
            path: 'M12 0C5.4 0 0 5.4 0 12c0 9 12 24 12 24s12-15 12-24c0-6.6-5.4-12-12-12zm0 18c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z',
            fillColor: config.primaryColor,
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 2,
            scale: 1.2,
            anchor: new google.maps.Point(12, 36)
        };

        const marker = new google.maps.Marker({
            position: position,
            map: map,
            icon: markerIcon,
            title: vertretung.title,
            animation: null
        });

        // Store reference
        marker.vertretungId = vertretung.id;
        markers.push(marker);

        // Click handler
        marker.addListener('click', () => {
            showInfoWindow(marker, vertretung);
            highlightCard(vertretung.id);
            scrollToCard(vertretung.id);
        });

        // Hover handler
        marker.addListener('mouseover', () => {
            marker.setAnimation(google.maps.Animation.BOUNCE);
            setTimeout(() => marker.setAnimation(null), 700);
        });

        return marker;
    }

    /**
     * Show info window for a marker
     */
    function showInfoWindow(marker, vertretung) {
        const content = `
            <div class="ibd-info-window">
                <h4 class="ibd-info-window-title">${escapeHtml(vertretung.title)}</h4>
                <div class="ibd-info-window-address">
                    ${vertretung.address.street ? escapeHtml(vertretung.address.street) + '<br>' : ''}
                    ${vertretung.address.zip || ''} ${vertretung.address.city || ''}<br>
                    ${vertretung.address.country || ''}
                </div>
                <button type="button" class="ibd-info-window-btn" onclick="document.querySelector('.ibd-card[data-id=\\'${vertretung.id}\\']').scrollIntoView({behavior: 'smooth', block: 'center'})">
                    ${config.i18n.showContacts || 'Details anzeigen'}
                </button>
            </div>
        `;

        infoWindow.setContent(content);
        infoWindow.open(map, marker);

        // Set active marker
        if (activeMarker) {
            resetMarkerIcon(activeMarker);
        }
        activeMarker = marker;
        setActiveMarkerIcon(marker);
    }

    /**
     * Set active marker icon
     */
    function setActiveMarkerIcon(marker) {
        marker.setIcon({
            path: 'M12 0C5.4 0 0 5.4 0 12c0 9 12 24 12 24s12-15 12-24c0-6.6-5.4-12-12-12zm0 18c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z',
            fillColor: '#333333',
            fillOpacity: 1,
            strokeColor: config.primaryColor,
            strokeWeight: 3,
            scale: 1.4,
            anchor: new google.maps.Point(12, 36)
        });
    }

    /**
     * Reset marker icon to default
     */
    function resetMarkerIcon(marker) {
        marker.setIcon({
            path: 'M12 0C5.4 0 0 5.4 0 12c0 9 12 24 12 24s12-15 12-24c0-6.6-5.4-12-12-12zm0 18c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z',
            fillColor: config.primaryColor,
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 2,
            scale: 1.2,
            anchor: new google.maps.Point(12, 36)
        });
    }

    /**
     * Initialize search functionality
     */
    function initSearch() {
        const searchInput = document.getElementById('ibd-search-input');
        const clearButton = document.getElementById('ibd-search-clear');

        if (!searchInput) return;

        let debounceTimer;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filterVertretungen(e.target.value);
            }, 300);
        });

        if (clearButton) {
            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                filterVertretungen('');
                searchInput.focus();
            });
        }
    }

    /**
     * Filter Vertretungen based on search query
     */
    function filterVertretungen(query) {
        query = query.toLowerCase().trim();
        const cards = document.querySelectorAll('.ibd-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const id = parseInt(card.dataset.id);
            const vertretung = vertretungenData.find(v => v.id === id);

            if (!vertretung) {
                card.style.display = 'none';
                return;
            }

            let match = false;

            if (!query) {
                match = true;
            } else {
                // Search in title
                if (vertretung.title.toLowerCase().includes(query)) {
                    match = true;
                }

                // Search in address
                if (!match) {
                    const addressString = [
                        vertretung.address.city,
                        vertretung.address.country
                    ].filter(Boolean).join(' ').toLowerCase();
                    if (addressString.includes(query)) {
                        match = true;
                    }
                }

                // Search in countries
                if (!match && vertretung.all_countries) {
                    for (const country of vertretung.all_countries) {
                        if (country.name.toLowerCase().includes(query)) {
                            match = true;
                            break;
                        }
                    }
                }

                // Search in contacts
                if (!match && vertretung.contacts_by_country) {
                    for (const group of vertretung.contacts_by_country) {
                        for (const contact of group.contacts || []) {
                            if (contact.name && contact.name.toLowerCase().includes(query)) {
                                match = true;
                                break;
                            }
                        }
                        if (match) break;
                    }
                }
            }

            card.style.display = match ? '' : 'none';
            if (match) visibleCount++;

            // Show/hide marker
            const marker = markers.find(m => m.vertretungId === id);
            if (marker) {
                marker.setVisible(match);
            }
        });

        updateResultsCount(visibleCount);

        // Adjust map bounds to visible markers
        if (map && query) {
            const visibleMarkers = markers.filter(m => m.getVisible());
            if (visibleMarkers.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                visibleMarkers.forEach(m => bounds.extend(m.getPosition()));
                map.fitBounds(bounds, { padding: 50 });
            }
        }
    }

    /**
     * Update results count display
     */
    function updateResultsCount(count) {
        const countElement = document.getElementById('ibd-results-count');
        if (countElement) {
            countElement.textContent = count + ' ' + (count === 1 ? 'Vertretung' : 'Vertretungen');
        }
    }

    /**
     * Initialize card interactions
     */
    function initCards() {
        const cards = document.querySelectorAll('.ibd-card');

        cards.forEach(card => {
            // Show on map button
            const showOnMapBtn = card.querySelector('.ibd-show-on-map');
            if (showOnMapBtn) {
                showOnMapBtn.addEventListener('click', () => {
                    const id = parseInt(card.dataset.id);
                    const lat = parseFloat(card.dataset.lat);
                    const lng = parseFloat(card.dataset.lng);

                    if (map && lat && lng) {
                        map.panTo({ lat, lng });
                        map.setZoom(8);

                        // Find and click the marker
                        const marker = markers.find(m => m.vertretungId === id);
                        if (marker) {
                            const vertretung = vertretungenData.find(v => v.id === id);
                            if (vertretung) {
                                showInfoWindow(marker, vertretung);
                            }
                        }

                        highlightCard(id);
                    }
                });
            }

            // Card hover - highlight marker
            card.addEventListener('mouseenter', () => {
                const id = parseInt(card.dataset.id);
                const marker = markers.find(m => m.vertretungId === id);
                if (marker) {
                    marker.setAnimation(google.maps.Animation.BOUNCE);
                    setTimeout(() => marker.setAnimation(null), 700);
                }
            });
        });
    }

    /**
     * Initialize accordions
     */
    function initAccordions() {
        const triggers = document.querySelectorAll('.ibd-accordion-trigger');

        triggers.forEach(trigger => {
            trigger.addEventListener('click', () => {
                const expanded = trigger.getAttribute('aria-expanded') === 'true';
                trigger.setAttribute('aria-expanded', !expanded);

                // Get country ISO codes for highlighting
                if (!expanded) {
                    const accordion = trigger.closest('.ibd-country-accordion');
                    const card = accordion.closest('.ibd-card');
                    const groupIndex = parseInt(accordion.dataset.group);
                    const vertretungId = parseInt(card.dataset.id);

                    const vertretung = vertretungenData.find(v => v.id === vertretungId);
                    if (vertretung && vertretung.contacts_by_country[groupIndex]) {
                        const countries = vertretung.contacts_by_country[groupIndex].countries || [];
                        highlightCountries(countries.map(c => c.iso_code).filter(Boolean));
                    }
                } else {
                    clearCountryHighlights();
                }
            });
        });
    }

    /**
     * Highlight card
     */
    function highlightCard(id) {
        // Remove previous highlight
        if (activeCard) {
            activeCard.classList.remove('active');
        }

        // Add highlight to new card
        const card = document.querySelector(`.ibd-card[data-id="${id}"]`);
        if (card) {
            card.classList.add('active');
            activeCard = card;
        }
    }

    /**
     * Scroll to card
     */
    function scrollToCard(id) {
        const card = document.querySelector(`.ibd-card[data-id="${id}"]`);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    /**
     * Highlight countries on map
     */
    function highlightCountries(isoCodes) {
        if (!map || !isoCodes.length) return;

        // Clear existing highlights
        clearCountryHighlights();

        // For each country, load and display GeoJSON
        isoCodes.forEach(code => {
            if (!code) return;

            fetch(`${config.restUrl}geojson/${code}`)
                .then(response => {
                    if (!response.ok) throw new Error('GeoJSON not found');
                    return response.json();
                })
                .then(geojson => {
                    const layer = new google.maps.Data();
                    layer.addGeoJson(geojson);
                    layer.setStyle({
                        fillColor: config.primaryColor,
                        fillOpacity: 0.2,
                        strokeColor: config.primaryColor,
                        strokeWeight: 2,
                        strokeOpacity: 0.8
                    });
                    layer.setMap(map);
                    countryLayers[code] = layer;
                })
                .catch(err => {
                    console.log('Could not load GeoJSON for', code);
                });
        });
    }

    /**
     * Clear country highlights
     */
    function clearCountryHighlights() {
        Object.values(countryLayers).forEach(layer => {
            layer.setMap(null);
        });
        countryLayers = {};
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also initialize when Google Maps is ready (in case it loads after DOM)
    window.initIBDMap = init;

})();
