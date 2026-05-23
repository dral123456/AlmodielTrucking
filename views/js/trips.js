$(document).ready(function () {
  const trips = Array.isArray(window.tripOverviewData) ? window.tripOverviewData : [];
  let filteredTrips = [];
  let selectedTripID = null;
  let map = null;
  let mapLayers = [];
  let tripDatePicker = null;
  const bookingDateCounts = buildBookingDateCounts();

  initMap();
  initDateRangePicker();
  bindEvents();
  renderTrips();

  function bindEvents() {
    $(document).on('change', '#tripSort, #tripStatusFilter, #tripDateRangeFilter', renderTrips);
    $(document).on('input', '#tripNumberFilter', renderTrips);

    $(document).on('click', '.trip-stat-card', function () {
      const status = $(this).data('status-shortcut');
      $('#tripStatusFilter').val(status);
      $('.trip-stat-card').removeClass('active');
      $(this).addClass('active');
      renderTrips();
    });

    $(document).on('change', '#tripStatusFilter', function () {
      const status = $(this).val();
      $('.trip-stat-card').removeClass('active');
      $('.trip-stat-card[data-status-shortcut="' + status + '"]').addClass('active');
    });

    $(document).on('click', '#tripClearFilters', function () {
      $('#tripSort').val('date_desc');
      $('#tripStatusFilter').val('all');
      $('#tripNumberFilter').val('');
      $('.trip-stat-card').removeClass('active');
      $('.trip-stat-card[data-status-shortcut="all"]').addClass('active');
      $('#tripDateRangeFilter').val('');
      if (tripDatePicker) {
        tripDatePicker.clear();
      }
      renderTrips();
    });

    $(document).on('click', '.trip-row', function () {
      selectedTripID = Number($(this).data('trip-id'));
      $('.trip-row').removeClass('active');
      $(this).addClass('active');
      renderTripDetails(getTripByID(selectedTripID));
    });

    $(document).on('click', '#toggleSidebar', function () {
      setTimeout(function () {
        if (map) {
          map.invalidateSize();
        }
      }, 250);
    });

    $(window).on('resize', function () {
      if (map) {
        map.invalidateSize();
      }
    });
  }

  function initDateRangePicker() {
    if (typeof AirDatepicker === 'undefined' || !document.getElementById('tripDateRangeFilter')) {
      return;
    }

    const localeEn = {
      days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
      daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
      daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
      months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
      monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      today: 'Today',
      clear: 'Clear',
      dateFormat: 'yyyy-MM-dd',
      timeFormat: 'hh:mm aa',
      firstDay: 0
    };

    tripDatePicker = new AirDatepicker('#tripDateRangeFilter', {
      range: true,
      multipleDatesSeparator: ' to ',
      dateFormat: 'yyyy-MM-dd',
      locale: localeEn,
      autoClose: false,
      buttons: ['today', 'clear'],
      onRenderCell: function ({ date, cellType }) {
        if (cellType !== 'day') {
          return {};
        }

        const dateKey = formatDateObject(date);
        const count = bookingDateCounts[dateKey] || 0;

        if (!count) {
          return {};
        }

        return {
          html: '<span class="trip-calendar-day">' + date.getDate() + '<i></i></span>',
          classes: '-trip-has-booking-',
          attrs: {
            title: count + ' booking(s)'
          }
        };
      },
      onSelect: function () {
        renderTrips();
      }
    });
  }

  function initMap() {
    if (typeof L === 'undefined' || !document.getElementById('tripMap')) {
      return;
    }

    if (map) {
      map.remove();
      map = null;
      mapLayers = [];
    }

    map = L.map('tripMap').setView([10.6765, 122.9509], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
  }

  function renderTrips() {
    filteredTrips = filterTrips();
    filteredTrips.sort(sortTrips);
    updateDateHint(filteredTrips.length);

    const html = filteredTrips.length
      ? filteredTrips.map(renderTripRow).join('')
      : '<tr><td colspan="6" class="text-center text-muted py-4">No trips found.</td></tr>';

    $('#tripTableBody').html(html);

    if (!filteredTrips.length) {
      selectedTripID = null;
      clearMap();
      $('#tripDetails').html('<div class="text-muted text-center p-4">No trips match the selected filters.</div>');
      $('#tripListSummary').text('No trips match the current filters.');
      return;
    }

    if (!selectedTripID || !getTripByID(selectedTripID, filteredTrips)) {
      selectedTripID = filteredTrips[0].tripID;
    }

    $('.trip-row[data-trip-id="' + selectedTripID + '"]').addClass('active');
    $('#tripListSummary').text(filteredTrips.length + ' trip(s) shown. Select a row to view route details.');
    renderTripDetails(getTripByID(selectedTripID, filteredTrips));
  }

  function filterTrips() {
    const status = $('#tripStatusFilter').val();
    const tripNumber = String($('#tripNumberFilter').val() || '').replace(/[^0-9]/g, '');
    const dateRange = parseDateRange($('#tripDateRangeFilter').val());

    return trips.filter(function (trip) {
      const tripDate = formatDateInput(trip.firstPickupDateTime);

      if (status !== 'all' && trip.status !== status) {
        return false;
      }

      if (tripNumber && String(trip.tripID).indexOf(tripNumber) === -1) {
        return false;
      }

      if (dateRange.from && tripDate < dateRange.from) {
        return false;
      }

      if (dateRange.to && tripDate > dateRange.to) {
        return false;
      }

      return true;
    });
  }

  function buildBookingDateCounts() {
    const counts = {};

    trips.forEach(function (trip) {
      (trip.bookings || []).forEach(function (booking) {
        const dateKey = formatDateInput(booking.pickupDateTime);
        counts[dateKey] = (counts[dateKey] || 0) + 1;
      });
    });

    return counts;
  }

  function updateDateHint(count) {
    const dates = Object.keys(bookingDateCounts);

    if (!dates.length) {
      $('#tripDateHint').text('No booking dates available yet.');
      return;
    }

    $('#tripDateHint').text(count + ' trip(s) shown. Dates with bookings are marked in the calendar.');
  }

  function sortTrips(a, b) {
    const sort = $('#tripSort').val();
    const aDate = new Date(a.firstPickupDateTime);
    const bDate = new Date(b.firstPickupDateTime);

    if (sort === 'date_asc') {
      return aDate - bDate;
    }

    if (sort === 'time_asc') {
      return minutesOfDay(aDate) - minutesOfDay(bDate);
    }

    if (sort === 'time_desc') {
      return minutesOfDay(bDate) - minutesOfDay(aDate);
    }

    return bDate - aDate;
  }

  function renderTripRow(trip) {
    const status = statusMeta(trip.status);
    const crew = formatCrew(trip.crew);
    const customerText = (trip.customers || []).join(', ') || '-';
    const bookings = trip.bookings || [];
    const firstBooking = bookings[0] || {};
    const lastBooking = bookings.length ? bookings[bookings.length - 1] : firstBooking;
    const routeStart = firstBooking.pickup && firstBooking.pickup.address ? firstBooking.pickup.address : '-';
    const routeEnd = lastBooking.destination && lastBooking.destination.address ? lastBooking.destination.address : '-';

    return (
      '<tr class="trip-row" data-trip-id="' + escapeHtml(trip.tripID) + '">' +
        '<td>' +
          '<div class="trip-row-main">Trip #' + escapeHtml(trip.tripID) + '</div>' +
          '<div class="trip-row-sub">' + escapeHtml(routeStart) + ' to ' + escapeHtml(routeEnd) + '</div>' +
        '</td>' +
        '<td>' + escapeHtml(formatDateTime(trip.firstPickupDateTime)) + '</td>' +
        '<td><div class="trip-row-sub">' + escapeHtml(customerText) + '</div></td>' +
        '<td><div class="trip-row-sub">' + escapeHtml(crew || '-') + '</div></td>' +
        '<td class="text-center"><span class="badge bg-light text-body">' + escapeHtml(trip.bookingCount) + '</span></td>' +
        '<td><span class="badge ' + status.className + '">' + status.label + '</span></td>' +
      '</tr>'
    );
  }

  function renderTripDetails(trip) {
    if (!trip) {
      $('#tripDetails').html('<div class="text-muted text-center p-4">Select a trip to view details.</div>');
      clearMap();
      return;
    }

    if (map) {
      map.remove();
      map = null;
      mapLayers = [];
    }

    const status = statusMeta(trip.status);
    const crew = formatCrew(trip.crew) || '-';
    const customerText = (trip.customers || []).join(', ') || '-';
    const bookingRows = (trip.bookings || []).map(function (booking) {
      return (
        '<div class="trip-booking-row">' +
          '<div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">' +
            '<div>' +
              '<div class="fw-semibold">Booking #' + escapeHtml(booking.bookingID) + ' - ' + escapeHtml(booking.customerName || '-') + '</div>' +
              '<div class="small text-muted">' + escapeHtml(formatDateTime(booking.pickupDateTime)) + ' | ' + escapeHtml(formatKilometers(booking.distanceKm)) + '</div>' +
            '</div>' +
            '<span class="badge bg-light text-body">' + escapeHtml(booking.customerType || '-') + '</span>' +
          '</div>' +
          '<div class="small mt-2"><i class="ri-box-3-line me-1"></i><strong>Cargo:</strong> ' + escapeHtml(booking.cargoSummary || 'No cargo recorded') + '</div>' +
          '<div class="trip-booking-locations small">' +
            '<div><i class="ri-map-pin-2-line text-primary me-1"></i>' + escapeHtml(booking.pickup.address || '-') + '</div>' +
            '<div><i class="ri-flag-line text-danger me-1"></i>' + escapeHtml(booking.destination.address || '-') + '</div>' +
          '</div>' +
        '</div>'
      );
    }).join('');

    $('#tripDetails').html(
      '<div class="trip-panel-heading mb-3">' +
        '<div>' +
          '<h6 class="mb-1">Trip #' + escapeHtml(trip.tripID) + '</h6>' +
          '<p class="text-muted small mb-0">' + escapeHtml(formatDateTime(trip.firstPickupDateTime)) + ' | ' + escapeHtml(trip.bookingCount) + ' booking(s)</p>' +
        '</div>' +
        '<span class="badge ' + status.className + '">' + status.label + '</span>' +
      '</div>' +
      '<div class="row g-3 mb-3">' +
        '<div class="col-12 col-lg-4"><div class="border rounded p-3 h-100"><span class="text-muted small d-block">Customer</span><strong>' + escapeHtml(customerText) + '</strong></div></div>' +
        '<div class="col-12 col-lg-4"><div class="border rounded p-3 h-100"><span class="text-muted small d-block">Trip KM</span><strong>' + escapeHtml(formatKilometers(trip.totalDistanceKm)) + '</strong></div></div>' +
        '<div class="col-12 col-lg-4"><div class="border rounded p-3 h-100"><span class="text-muted small d-block">Crew</span><strong>' + escapeHtml(crew) + '</strong></div></div>' +
      '</div>' +
      '<div class="trip-detail-grid">' +
        '<div>' +
          '<h6 class="mb-3"><i class="ri-file-list-3-line me-1"></i> Connected Bookings</h6>' +
          '<div class="trip-booking-list">' + (bookingRows || '<div class="text-muted border rounded p-3">No bookings attached.</div>') + '</div>' +
        '</div>' +
        '<div class="trip-map-shell">' +
          '<div class="trip-panel-heading mb-3">' +
            '<div>' +
              '<h6 class="mb-0"><i class="ri-road-map-line me-1"></i> Route Map</h6>' +
              '<p class="text-muted small mb-0" id="tripMapStatus">Showing pickup and destination pins for Trip #' + escapeHtml(trip.tripID) + '.</p>' +
            '</div>' +
            '<span class="badge bg-secondary-subtle text-secondary" id="tripMapBadge">' + escapeHtml((trip.bookings || []).length) + ' booking(s)</span>' +
          '</div>' +
          '<div id="tripMap"></div>' +
        '</div>' +
      '</div>'
    );

    initMap();
    renderTripMap(trip);
  }

  function renderTripMap(trip) {
    if (!map || !trip) {
      return;
    }

    clearMap();
    const bounds = [];

    (trip.bookings || []).forEach(function (booking, index) {
      const pickupLatLng = [booking.pickup.latitude, booking.pickup.longitude];
      const destinationLatLng = [booking.destination.latitude, booking.destination.longitude];

      const pickupMarker = L.marker(pickupLatLng, { icon: markerIcon('#696cff', 'P' + (index + 1)) })
        .bindPopup('<strong>Pickup</strong><br>Booking #' + escapeHtml(booking.bookingID) + '<br>' + escapeHtml(booking.pickup.address || '-'));
      const destinationMarker = L.marker(destinationLatLng, { icon: markerIcon('#ff3e1d', 'D' + (index + 1)) })
        .bindPopup('<strong>Destination</strong><br>Booking #' + escapeHtml(booking.bookingID) + '<br>' + escapeHtml(booking.destination.address || '-'));
      const line = L.polyline([pickupLatLng, destinationLatLng], {
        color: '#696cff',
        weight: 3,
        opacity: 0.75
      });

      pickupMarker.addTo(map);
      destinationMarker.addTo(map);
      line.addTo(map);

      mapLayers.push(pickupMarker, destinationMarker, line);
      bounds.push(pickupLatLng, destinationLatLng);
    });

    $('#tripMapStatus').text('Showing pickup and destination pins for Trip #' + trip.tripID + '.');
    $('#tripMapBadge').text((trip.bookings || []).length + ' booking(s)');

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

  function markerIcon(color, label) {
    return L.divIcon({
      className: '',
      html: '<span style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:' + color + ';color:#fff;border:2px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.3);font-size:11px;font-weight:700;">' + label + '</span>',
      iconSize: [28, 28],
      iconAnchor: [14, 14]
    });
  }

  function getTripByID(tripID, source) {
    return (source || trips).find(function (trip) {
      return Number(trip.tripID) === Number(tripID);
    });
  }

  function statusMeta(status) {
    if (status === 'completed') {
      return { label: 'Delivered', className: 'bg-success-subtle text-success' };
    }

    if (status === 'in-transit') {
      return { label: 'On Transit', className: 'bg-info-subtle text-info' };
    }

    if (status === 'stopover') {
      return { label: 'Stopover', className: 'bg-primary-subtle text-primary' };
    }

    return { label: 'Pending', className: 'bg-warning-subtle text-warning' };
  }

  function formatCrew(crew) {
    if (!Array.isArray(crew) || !crew.length) {
      return '';
    }

    return crew.map(function (member) {
      const name = [member.empFName, member.empLName].filter(Boolean).join(' ');
      return member.role + ': ' + name;
    }).join(', ');
  }

  function formatDateInput(value) {
    if (!value) {
      return '';
    }

    return value.replace(' ', 'T').slice(0, 10);
  }

  function formatDateObject(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return year + '-' + month + '-' + day;
  }

  function parseDateRange(value) {
    const dates = String(value || '').match(/\d{4}-\d{2}-\d{2}/g) || [];
    const from = dates[0] || '';
    const to = dates[1] || from;

    if (from && to && from > to) {
      return { from: to, to: from };
    }

    return { from, to };
  }

  function formatDateTime(value) {
    if (!value) {
      return '-';
    }

    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
      return value;
    }

    return date.toLocaleString([], {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function minutesOfDay(date) {
    return date.getHours() * 60 + date.getMinutes();
  }

  function formatKilometers(value) {
    const distance = Number(value || 0);
    if (!Number.isFinite(distance) || distance <= 0) {
      return '0.00 km';
    }

    return distance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' km';
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
