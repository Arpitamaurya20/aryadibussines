<?php
$attendance_lat = $employee_data['AttendanceLatitude'] ?? '';
$attendance_lng = $employee_data['AttendanceLongitude'] ?? '';
$attendance_radius = (int) ($employee_data['AttendanceRadiusMeters'] ?? 100);
if ($attendance_radius <= 0) {
    $attendance_radius = 100;
}
$is_boundary_enabled = !empty($employee_data['IsAllowLocationBoundary']);
$map_lat = ($attendance_lat !== '' && is_numeric($attendance_lat)) ? (float) $attendance_lat : 28.6139;
$map_lng = ($attendance_lng !== '' && is_numeric($attendance_lng)) ? (float) $attendance_lng : 77.2090;
$has_marker = ($attendance_lat !== '' && $attendance_lng !== '' && is_numeric($attendance_lat) && is_numeric($attendance_lng));
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
#employee_attendance_map {
    height: 420px;
    width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    z-index: 1;
}
</style>

<div class="profile-grid-premium">
    <div class="profile-card-premium" style="grid-column: 1 / -1;">
        <div class="profile-card-hdr">
            <i class="fal fa-map-marked-alt"></i>
            <h3>Attendance Location Boundary</h3>
        </div>
        <p class="profile-lbl mt-2" style="font-size: 13px; text-transform: none;">Enter latitude and longitude, then click <strong>Show on map</strong>. You can also click the map to set coordinates.</p>
        
        <div class="mt-3">
            <form id="save_employee_location">
                <input type="hidden" name="EmployeeID" value="<?= (int) $ID; ?>">

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="is_allow_location_boundary"
                        name="is_allow_location_boundary" value="1" <?= $is_boundary_enabled ? 'checked' : ''; ?>>
                    <label class="form-check-label profile-lbl" for="is_allow_location_boundary" style="font-size: 13px;">
                        Enable location boundary for attendance
                    </label>
                </div>

                <div class="row mb-2">
                    <div class="col-md-4">
                        <label for="attendance_latitude" class="profile-lbl">Latitude</label>
                        <input type="text" class="form-control" id="attendance_latitude" name="attendance_latitude"
                            value="<?= htmlspecialchars((string) $attendance_lat, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="e.g. 28.613939">
                    </div>
                    <div class="col-md-4">
                        <label for="attendance_longitude" class="profile-lbl">Longitude</label>
                        <input type="text" class="form-control" id="attendance_longitude" name="attendance_longitude"
                            value="<?= htmlspecialchars((string) $attendance_lng, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="e.g. 77.209023">
                    </div>
                    <div class="col-md-4">
                        <label for="attendance_radius_meters" class="profile-lbl">Radius (meters)</label>
                        <input type="number" class="form-control" id="attendance_radius_meters" name="attendance_radius_meters"
                            min="10" max="50000" step="10" value="<?= $attendance_radius; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn-premium" style="padding: 6px 16px; font-size: 13px;" id="showOnMapBtn">
                        <i class="fal fa-map-marker-alt"></i> Show on map
                    </button>
                </div>

                <div id="employee_attendance_map" class="mb-3" style="border-radius: 12px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;"></div>

                <div id="locationMessage" class="mb-2"></div>

                <button type="submit" class="btn-premium w-100 justify-content-center" id="saveLocationBtn">
                    <i class="fal fa-save"></i> Save Location
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
window.EmployeeLocationTabConfig = {
    defaultCenter: { lat: <?= json_encode($map_lat); ?>, lng: <?= json_encode($map_lng); ?> },
    hasMarker: <?= $has_marker ? 'true' : 'false'; ?>
};

window.bootEmployeeLocationTab = function () {
    if (window._employeeLocationTabReady) {
        if (window._employeeLocationMapRefresh) {
            window._employeeLocationMapRefresh();
        }
        return;
    }
    window._employeeLocationTabReady = true;

    if (typeof L === 'undefined') {
        document.getElementById('locationMessage').innerHTML =
            '<div class="alert alert-danger">Map library failed to load. Check your internet connection and refresh.</div>';
        return;
    }

    const defaultCenter = window.EmployeeLocationTabConfig.defaultCenter;
    const hasMarker = window.EmployeeLocationTabConfig.hasMarker;
    let map = null;
    let marker = null;
    let circle = null;
    let mapInitialized = false;

    const latInput = document.getElementById('attendance_latitude');
    const lngInput = document.getElementById('attendance_longitude');
    const radiusInput = document.getElementById('attendance_radius_meters');
    const mapEl = document.getElementById('employee_attendance_map');

    function parseCoord(value) {
        const cleaned = String(value).trim().replace(',', '.');
        const num = parseFloat(cleaned);
        return isNaN(num) ? null : num;
    }

    function getRadius() {
        const r = parseInt(radiusInput.value, 10);
        return (isNaN(r) || r < 10) ? 100 : r;
    }

    function getCoordsFromInputs() {
        const lat = parseCoord(latInput.value);
        const lng = parseCoord(lngInput.value);
        if (lat === null || lng === null || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            return null;
        }
        return { lat: lat, lng: lng };
    }

    function showCoordError(msg) {
        document.getElementById('locationMessage').innerHTML =
            '<div class="alert alert-warning">' + msg + '</div>';
    }

    function clearCoordError() {
        const el = document.getElementById('locationMessage');
        if (el.querySelector('.alert-warning')) {
            el.innerHTML = '';
        }
    }

    function updateCircle() {
        if (!marker || !circle) return;
        const pos = marker.getLatLng();
        circle.setLatLng(pos);
        circle.setRadius(getRadius());
    }

    function attachMarkerDrag() {
        marker.on('dragend', function () {
            const p = marker.getLatLng();
            latInput.value = p.lat.toFixed(6);
            lngInput.value = p.lng.toFixed(6);
            updateCircle();
        });
    }

    function setPosition(lat, lng, flyToLocation) {
        if (!map) return false;
        const pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
        if (isNaN(pos.lat) || isNaN(pos.lng)) return false;

        latInput.value = pos.lat.toFixed(6);
        lngInput.value = pos.lng.toFixed(6);

        if (!marker) {
            marker = L.marker([pos.lat, pos.lng], { draggable: true }).addTo(map);
            circle = L.circle([pos.lat, pos.lng], {
                radius: getRadius(),
                color: '#1976D2',
                fillColor: '#2196F3',
                fillOpacity: 0.2,
                weight: 2
            }).addTo(map);
            attachMarkerDrag();
        } else {
            marker.setLatLng([pos.lat, pos.lng]);
            updateCircle();
        }

        if (flyToLocation) {
            map.flyTo([pos.lat, pos.lng], 16, { duration: 0.6 });
        } else {
            map.setView([pos.lat, pos.lng], map.getZoom() < 14 ? 15 : map.getZoom());
        }

        clearCoordError();
        setTimeout(function () { map.invalidateSize(); }, 100);
        return true;
    }

    function initMap() {
        if (mapInitialized && map) {
            map.invalidateSize();
            return;
        }

        const coords = getCoordsFromInputs();
        const start = coords || defaultCenter;
        const zoom = coords ? 15 : (hasMarker ? 15 : 5);

        map = L.map(mapEl).setView([start.lat, start.lng], zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        map.on('click', function (e) {
            setPosition(e.latlng.lat, e.latlng.lng, false);
        });

        mapInitialized = true;

        if (coords) {
            setPosition(coords.lat, coords.lng, false);
        } else if (hasMarker) {
            setPosition(defaultCenter.lat, defaultCenter.lng, false);
        }

        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    function applyCoordsFromInputs(flyToLocation) {
        const coords = getCoordsFromInputs();
        if (!coords) {
            showCoordError('Enter valid latitude (-90 to 90) and longitude (-180 to 180) in both fields.');
            return false;
        }

        const alreadyReady = mapInitialized;
        initMap();
        setTimeout(function () {
            setPosition(coords.lat, coords.lng, flyToLocation !== false);
        }, alreadyReady ? 80 : 350);

        return true;
    }

    window._employeeLocationMapRefresh = function () {
        initMap();
        const coords = getCoordsFromInputs();
        if (coords) {
            setPosition(coords.lat, coords.lng, true);
        }
    };

    radiusInput.addEventListener('input', updateCircle);

    document.getElementById('showOnMapBtn').addEventListener('click', function () {
        applyCoordsFromInputs(true);
    });

    latInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyCoordsFromInputs(true);
        }
    });
    lngInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyCoordsFromInputs(true);
        }
    });

    document.getElementById('save_employee_location').addEventListener('submit', function (e) {
        e.preventDefault();
        const saveBtn = document.getElementById('saveLocationBtn');
        const boundaryOn = document.getElementById('is_allow_location_boundary').checked;
        if (boundaryOn && !getCoordsFromInputs()) {
            document.getElementById('locationMessage').innerHTML =
                '<div class="alert alert-warning">No work location set. Check-in will use active branch locations when boundary is enabled.</div>';
        }
        saveBtn.disabled = true;
        saveBtn.innerText = 'Saving...';
        fetch('action/save_employee_location.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            const cls = data.status === 'success' ? 'alert-success' : 'alert-danger';
            document.getElementById('locationMessage').innerHTML =
                '<div class="alert ' + cls + '">' + (data.message || '') + '</div>';
            saveBtn.disabled = false;
            saveBtn.innerText = 'Save Location';
        })
        .catch(function () {
            document.getElementById('locationMessage').innerHTML =
                '<div class="alert alert-danger">Could not save. Run the SQL migration if you have not already.</div>';
            saveBtn.disabled = false;
            saveBtn.innerText = 'Save Location';
        });
    });

    var locationTabLink = document.querySelector('a[href="#location"]');
    if (locationTabLink) {
        locationTabLink.addEventListener('click', function () {
            setTimeout(window._employeeLocationMapRefresh, 300);
        });
    }

    if (typeof jQuery !== 'undefined') {
        jQuery('a[href="#location"]').on('shown.bs.tab', function () {
            setTimeout(window._employeeLocationMapRefresh, 200);
        });
    }
};
</script>
