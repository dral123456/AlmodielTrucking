$(document).ready(function () {
  const trips = Array.isArray(window.driverTripData) ? window.driverTripData : [];
  let map = null;
  let mapLayers = [];

  initMap();
  if (trips.length) {
    selectTrip(trips[0].tripID);
  }

  $(document).on('click', '.driver-map-focus, .driver-trip-row', function (event) {
    if ($(event.target).closest('.driver-status-action').length) {
      return;
    }

    const row = $(this).hasClass('driver-trip-row') ? $(this) : $(this).closest('.driver-trip-row');
    selectTrip(row.data('trip-id'));
  });

  $(document).on('click', '.driver-status-action', function () {
    const button = $(this);
    const row = button.closest('.driver-trip-row');
    const tripID = row.data('trip-id');
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
          row.fadeOut(180, function () {
            $(this).remove();
            if (!$('.driver-trip-row').length) {
              $('.driver-trip-list').replaceWith('<div class="driver-empty">No active trips assigned yet.</div>');
              clearMap();
              $('#driverMapStatus').text('No active trips assigned yet.');
              $('#driverMapBadge').text('No trip selected');
            } else {
              selectTrip($('.driver-trip-row').first().data('trip-id'));
            }
          });
          return;
        }

        if (status === 'in-transit') {
          row.find('.driver-trip-status')
            .removeClass('bg-warning-subtle text-warning bg-info-subtle text-info bg-success-subtle text-success')
            .addClass('bg-primary-subtle text-primary')
            .text('In transit');
          updateTripStatus(tripID, 'in-transit');
          button.remove();
          return;
        }

        row.find('.driver-trip-status')
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
    $('.driver-trip-row').removeClass('active');
    $('.driver-trip-row[data-trip-id="' + tripID + '"]').addClass('active');
    renderMap(trip);
    renderTripDetails(trip);
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
    let visiblePins = 0;
    let missingPins = 0;

    (trip.bookings || []).forEach(function (booking, index) {
      const pickup = [
        Number(booking.pickupLatitude),
        Number(booking.pickupLongitude)
      ];
      const destination = [
        Number(booking.destinationLatitude),
        Number(booking.destinationLongitude)
      ];

      const hasPickup = validPoint(pickup);
      const hasDestination = validPoint(destination);

      if (hasPickup) {
        const pickupMarker = L.marker(pickup, { icon: markerIcon('#696cff', 'P' + (index + 1)) })
          .bindPopup('<strong>Pickup</strong><br>Booking #' + escapeHtml(booking.bookingID) + '<br>' + escapeHtml(booking.pickupAddress || '-'));

        pickupMarker.addTo(map);
        mapLayers.push(pickupMarker);
        bounds.push(pickup);
        visiblePins++;
      } else {
        missingPins++;
      }

      if (hasDestination) {
        const destinationMarker = L.marker(destination, { icon: markerIcon('#ff3e1d', 'D' + (index + 1)) })
          .bindPopup('<strong>Destination</strong><br>Booking #' + escapeHtml(booking.bookingID) + '<br>' + escapeHtml(booking.destinationAddress || '-'));

        destinationMarker.addTo(map);
        mapLayers.push(destinationMarker);
        bounds.push(destination);
        visiblePins++;
      } else {
        missingPins++;
      }

      if (hasPickup && hasDestination) {
        const routeLine = L.polyline([pickup, destination], {
          color: '#696cff',
          weight: 3,
          opacity: 0.75
        });

        routeLine.addTo(map);
        mapLayers.push(routeLine);
      }
    });

    if (visiblePins && missingPins) {
      $('#driverMapStatus').text('Showing available pins for Trip #' + trip.tripID + '. Some pickup/destination coordinates are missing.');
    } else if (visiblePins) {
      $('#driverMapStatus').text('Showing pickup and destination pins for Trip #' + trip.tripID + '.');
    } else {
      $('#driverMapStatus').text('This trip has invalid or missing map coordinates. Please check the booking location pins.');
    }
    $('#driverMapBadge').text((trip.bookings || []).length + ' booking(s)');

    if (bounds.length) {
      map.fitBounds(bounds, { padding: [28, 28] });
    }

    setTimeout(function () {
      map.invalidateSize();
    }, 100);
  }

  function renderTripDetails(trip) {
    const panel = $('#driverTripDetailPanel');

    if (!panel.length) {
      return;
    }

    if (!trip || !Array.isArray(trip.bookings) || !trip.bookings.length) {
      panel.html('<div class="driver-trip-detail-empty">Select a trip to view booking details.</div>');
      return;
    }

    const bookingCards = trip.bookings.map(function (booking, index) {
      return '' +
        '<div class="driver-trip-detail-card">' +
          '<div class="driver-trip-detail-title">' +
            '<div>' +
              '<strong>Booking #' + escapeHtml(booking.bookingID) + ' - ' + escapeHtml(booking.customerName || 'Customer') + '</strong>' +
              '<span>' + escapeHtml(booking.customerType || '-') + '</span>' +
            '</div>' +
            '<span class="badge bg-secondary-subtle text-secondary">#' + (index + 1) + '</span>' +
          '</div>' +
          '<div class="driver-trip-detail-grid">' +
            '<div>' +
              '<small><i class="ri-map-pin-2-line text-primary me-1"></i>Pickup</small>' +
              '<p>' + escapeHtml(booking.pickupAddress || '-') + '</p>' +
              (booking.pickupDescription && booking.pickupDescription !== booking.pickupAddress ? '<span>' + escapeHtml(booking.pickupDescription) + '</span>' : '') +
            '</div>' +
            '<div>' +
              '<small><i class="ri-flag-line text-danger me-1"></i>Destination</small>' +
              '<p>' + escapeHtml(booking.destinationAddress || '-') + '</p>' +
              (booking.destinationDescription && booking.destinationDescription !== booking.destinationAddress ? '<span>' + escapeHtml(booking.destinationDescription) + '</span>' : '') +
            '</div>' +
          '</div>' +
        '</div>';
    }).join('');

    panel.html(
      '<div class="driver-trip-detail-heading">' +
        '<div>' +
          '<h6 class="mb-1">Trip #' + escapeHtml(trip.tripID) + ' Details</h6>' +
          '<p class="text-muted small mb-0">' + escapeHtml((trip.bookings || []).length) + ' booking(s) attached to this delivery.</p>' +
        '</div>' +
      '</div>' +
      '<div class="driver-trip-detail-list">' + bookingCards + '</div>'
    );
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
