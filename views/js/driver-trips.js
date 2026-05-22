$(document).ready(function () {
  const trips = Array.isArray(window.driverTripData) ? window.driverTripData : [];
  let map = null;
  let mapLayers = [];

  initMap();
  if (trips.length) {
    selectTrip(trips[0].tripID);
  }

  $(document).on('click', '.driver-map-focus, .driver-trip-card', function (event) {
    if ($(event.target).closest('.driver-status-action').length) {
      return;
    }

    const card = $(this).hasClass('driver-trip-card') ? $(this) : $(this).closest('.driver-trip-card');
    selectTrip(card.data('trip-id'));
  });

  $(document).on('click', '.driver-status-action', function () {
    const button = $(this);
    const card = button.closest('.driver-trip-card');
    const tripID = card.data('trip-id');
    const status = button.data('status');
    const labels = {
      'in-transit': 'start this delivery',
      'stopover': 'mark this trip as stopover',
      'completed': 'mark this trip as delivered'
    };
    const label = labels[status] || 'update this trip';

    if (!window.confirm('Are you sure you want to ' + label + '?')) {
      return;
    }

    button.prop('disabled', true);

    $.ajax({
      url: 'ajax/driver_trip_status.ajax.php',
      method: 'POST',
      dataType: 'json',
      data: {
        tripID: tripID,
        status: status
      },
      success: function (response) {
        if (!response || response.status !== 'success') {
          alert(response && response.message ? response.message : 'Unable to update trip.');
          button.prop('disabled', false);
          return;
        }

        if (status === 'completed') {
          card.slideUp(180, function () {
            $(this).remove();
            if (!$('.driver-trip-card').length) {
              $('.driver-trip-list').replaceWith('<div class="driver-empty">No active trips assigned yet.</div>');
              clearMap();
              $('#driverMapStatus').text('No active trips assigned yet.');
              $('#driverMapBadge').text('No trip selected');
            } else {
              selectTrip($('.driver-trip-card').first().data('trip-id'));
            }
          });
          return;
        }

        if (status === 'in-transit') {
          card.find('.driver-trip-status')
            .removeClass('bg-warning-subtle text-warning bg-info-subtle text-info bg-success-subtle text-success')
            .addClass('bg-primary-subtle text-primary')
            .text('In transit');
          updateTripStatus(tripID, 'in-transit');
          button.remove();
          return;
        }

        card.find('.driver-trip-status')
          .removeClass('bg-warning-subtle text-warning bg-primary-subtle text-primary bg-success-subtle text-success')
          .addClass('bg-info-subtle text-info')
          .text('Stopover');
        updateTripStatus(tripID, 'stopover');
        button.prop('disabled', false);
      },
      error: function () {
        alert('Unable to update trip.');
        button.prop('disabled', false);
      }
    });
  });

  function initMap() {
    if (typeof L === 'undefined' || !document.getElementById('driverTripMap')) {
      return;
    }

    map = L.map('driverTripMap').setView([10.6765, 122.9509], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    setTimeout(function () {
      map.invalidateSize();
    }, 100);
  }

  function selectTrip(tripID) {
    const trip = getTrip(tripID);
    $('.driver-trip-card').removeClass('active');
    $('.driver-trip-card[data-trip-id="' + tripID + '"]').addClass('active');
    renderMap(trip);
  }

  function getTrip(tripID) {
    return trips.find(function (trip) {
      return Number(trip.tripID) === Number(tripID);
    });
  }

  function updateTripStatus(tripID, status) {
    const trip = getTrip(tripID);
    if (!trip) {
      return;
    }

    trip.status = status;
    (trip.bookings || []).forEach(function (booking) {
      booking.status = status;
    });
    renderMap(trip);
  }

  function renderMap(trip) {
    if (!map || !trip) {
      return;
    }

    clearMap();
    const bounds = [];

    (trip.bookings || []).forEach(function (booking, index) {
      const pickup = [
        Number(booking.pickupLatitude),
        Number(booking.pickupLongitude)
      ];
      const destination = [
        Number(booking.destinationLatitude),
        Number(booking.destinationLongitude)
      ];

      if (!validPoint(pickup) || !validPoint(destination)) {
        return;
      }

      const pickupMarker = L.marker(pickup, { icon: markerIcon('#696cff', 'P' + (index + 1)) })
        .bindPopup('<strong>Pickup</strong><br>Booking #' + escapeHtml(booking.bookingID) + '<br>' + escapeHtml(booking.pickupAddress || '-'));
      const destinationMarker = L.marker(destination, { icon: markerIcon('#ff3e1d', 'D' + (index + 1)) })
        .bindPopup('<strong>Destination</strong><br>Booking #' + escapeHtml(booking.bookingID) + '<br>' + escapeHtml(booking.destinationAddress || '-'));
      const routeLine = L.polyline([pickup, destination], {
        color: '#696cff',
        weight: 3,
        opacity: 0.75
      });

      pickupMarker.addTo(map);
      destinationMarker.addTo(map);
      routeLine.addTo(map);
      mapLayers.push(pickupMarker, destinationMarker, routeLine);
      bounds.push(pickup, destination);
    });

    $('#driverMapStatus').text('Showing pickup and destination pins for Trip #' + trip.tripID + '.');
    $('#driverMapBadge').text((trip.bookings || []).length + ' booking(s)');

    if (bounds.length) {
      map.fitBounds(bounds, { padding: [28, 28] });
    }

    setTimeout(function () {
      map.invalidateSize();
    }, 100);
  }

  function clearMap() {
    if (!map) {
      return;
    }

    mapLayers.forEach(function (layer) {
      map.removeLayer(layer);
    });
    mapLayers = [];
  }

  function validPoint(point) {
    return Number.isFinite(point[0]) && Number.isFinite(point[1]) && point[0] !== 0 && point[1] !== 0;
  }

  function markerIcon(color, label) {
    return L.divIcon({
      className: '',
      html: '<span style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:' + color + ';color:#fff;border:2px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.3);font-size:11px;font-weight:700;">' + label + '</span>',
      iconSize: [28, 28],
      iconAnchor: [14, 14]
    });
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
