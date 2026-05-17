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

    $(document).on('click', '#tripClearFilters', function () {
      $('#tripSort').val('date_desc');
      $('#tripStatusFilter').val('all');
      $('#tripDateRangeFilter').val('');
      if (tripDatePicker) {
        tripDatePicker.clear();
      }
      renderTrips();
    });

    $(document).on('click', '.trip-item', function () {
      selectedTripID = Number($(this).data('trip-id'));
      $('.trip-item').removeClass('active');
      $(this).addClass('active');
      renderTripMap(getTripByID(selectedTripID));
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
      ? filteredTrips.map(renderTripItem).join('')
      : '<div class="text-center text-muted border rounded p-4">No trips found.</div>';

    $('#tripList').html(html);

    if (!filteredTrips.length) {
      selectedTripID = null;
      clearMap();
      $('#tripMapStatus').text('No trips match the selected filters.');
      $('#tripMapBadge').text('No trip selected');
      return;
    }

    if (!selectedTripID || !getTripByID(selectedTripID, filteredTrips)) {
      selectedTripID = filteredTrips[0].tripID;
    }

    $('.trip-item[data-trip-id="' + selectedTripID + '"]').addClass('active');
    renderTripMap(getTripByID(selectedTripID, filteredTrips));
  }

  function filterTrips() {
    const status = $('#tripStatusFilter').val();
    const dateRange = parseDateRange($('#tripDateRangeFilter').val());

    return trips.filter(function (trip) {
      const tripDate = formatDateInput(trip.firstPickupDateTime);

      if (status !== 'all' && trip.status !== status) {
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

  function renderTripItem(trip) {
    const status = statusMeta(trip.status);
    const crew = formatCrew(trip.crew);
    const customerText = (trip.customers || []).join(', ') || '-';
    const bookingRows = (trip.bookings || []).map(function (booking) {
      return (
        '<div class="trip-booking-row">' +
          '<div class="fw-semibold">Booking #' + escapeHtml(booking.bookingID) + ' - ' + escapeHtml(booking.customerName || '-') + '</div>' +
          '<div class="small text-muted">' + escapeHtml(formatDateTime(booking.pickupDateTime)) + '</div>' +
          '<div class="small mt-1"><i class="ri-map-pin-2-line text-primary me-1"></i>' + escapeHtml(booking.pickup.address || '-') + '</div>' +
          '<div class="small"><i class="ri-flag-line text-danger me-1"></i>' + escapeHtml(booking.destination.address || '-') + '</div>' +
        '</div>'
      );
    }).join('');

    return (
      '<button type="button" class="trip-item" data-trip-id="' + escapeHtml(trip.tripID) + '">' +
        '<div class="d-flex align-items-start justify-content-between gap-2">' +
          '<div>' +
            '<h6 class="mb-1">Trip #' + escapeHtml(trip.tripID) + '</h6>' +
            '<div class="trip-meta">' +
              '<span><i class="ri-calendar-line me-1"></i>' + escapeHtml(formatDateTime(trip.firstPickupDateTime)) + '</span>' +
              '<span><i class="ri-file-list-3-line me-1"></i>' + escapeHtml(trip.bookingCount) + ' booking(s)</span>' +
            '</div>' +
          '</div>' +
          '<span class="badge ' + status.className + '">' + status.label + '</span>' +
        '</div>' +
        '<div class="small text-muted mt-2">Customer(s): ' + escapeHtml(customerText) + '</div>' +
        '<div class="small text-muted">Crew: ' + escapeHtml(crew || '-') + '</div>' +
        bookingRows +
      '</button>'
    );
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

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
