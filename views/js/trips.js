$(document).ready(function () {
  const trips = Array.isArray(window.tripOverviewData) ? window.tripOverviewData : [];
  let filteredTrips = [];
  let selectedTripID = null;
  let map = null;
  let mapLayers = [];
  let tripDatePicker = null;
  let editTripMap = null;
  let editTripMarker = null;
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

    $(document).on('click', '#tripModifyBtn', function () {
      const trip = getTripByID(selectedTripID);
      if (trip) {
        showTripEditModal(trip);
      }
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
        '<div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">' +
          '<span class="badge ' + status.className + '">' + status.label + '</span>' +
          '<button type="button" class="btn btn-sm btn-primary" id="tripModifyBtn"><i class="ri-edit-line me-1"></i> Modify</button>' +
        '</div>' +
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

  function showTripEditModal(trip) {
    const status = trip.status || 'pending';
    const truckID = getTripTruckID(trip);
    const driverID = getTripDriverID(trip);
    const assistantIDs = getTripAssistantIDs(trip);
    const firstBooking = (trip.bookings || [])[0] || {};

    Swal.fire({
      title: 'Modify Trip #' + trip.tripID,
      html:
        '<div class="text-start">' +
          '<div class="row g-3">' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Trip Schedule</label>' +
              '<input type="datetime-local" class="form-control" id="editTripPickupDateTime" value="' + escapeAttr(toDateTimeLocalValue(trip.firstPickupDateTime)) + '">' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Status</label>' +
              '<select class="form-select" id="editTripStatus">' +
                '<option value="pending"' + (status === 'pending' ? ' selected' : '') + '>Pending</option>' +
                '<option value="in-transit"' + (status === 'in-transit' ? ' selected' : '') + '>On Transit</option>' +
                '<option value="stopover"' + (status === 'stopover' ? ' selected' : '') + '>Stopover</option>' +
                '<option value="completed"' + (status === 'completed' ? ' selected' : '') + '>Delivered</option>' +
              '</select>' +
            '</div>' +
            '<div class="col-12">' +
              '<label class="form-label">Truck</label>' +
              '<select class="form-select" id="editTripTruck">' + buildTruckOptions(truckID) + '</select>' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Driver</label>' +
              '<select class="form-select" id="editTripDriver">' + buildEmployeeOptions(window.tripDriverOptions || [], driverID, 'Select driver') + '</select>' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Assistants</label>' +
              '<select class="form-select" id="editTripAssistants" multiple size="5">' + buildEmployeeOptions(window.tripAssistantOptions || [], assistantIDs, 'Select assistants') + '</select>' +
            '</div>' +
            '<div class="col-12">' +
              '<hr class="my-1">' +
              '<label class="form-label">Booking Destination To Modify</label>' +
              '<select class="form-select" id="editTripBooking">' + buildBookingOptions(trip.bookings || []) + '</select>' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Fuel Pump Price</label>' +
              '<input type="number" min="0" step="0.01" class="form-control" id="editTripFuelPrice" placeholder="e.g. 76">' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Price</label>' +
              '<input type="number" min="0" step="0.01" class="form-control" id="editTripPrice">' +
              '<div class="form-text" id="editTripTariffHint">Fuel pump is used to recalculate tariff price when available.</div>' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Province</label>' +
              '<input type="text" class="form-control edit-trip-destination-field" id="editTripDestinationProvince">' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">City</label>' +
              '<input type="text" class="form-control edit-trip-destination-field" id="editTripDestinationCity">' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Barangay</label>' +
              '<input type="text" class="form-control edit-trip-destination-field" id="editTripDestinationBarangay">' +
            '</div>' +
            '<div class="col-12 col-md-6">' +
              '<label class="form-label">Street</label>' +
              '<input type="text" class="form-control edit-trip-destination-field" id="editTripDestinationStreet">' +
            '</div>' +
            '<div class="col-12">' +
              '<label class="form-label">Destination Notes</label>' +
              '<textarea class="form-control edit-trip-destination-field" id="editTripDestinationDescription" rows="2"></textarea>' +
            '</div>' +
            '<div class="col-12">' +
              '<label class="form-label">Destination Map</label>' +
              '<div id="editTripDestinationMap" style="height:320px;border:1px solid var(--bs-border-color);border-radius:8px;overflow:hidden;"></div>' +
              '<input type="hidden" id="editTripDestinationLatitude">' +
              '<input type="hidden" id="editTripDestinationLongitude">' +
            '</div>' +
          '</div>' +
          '<p class="text-muted small mb-0 mt-3">Schedule, status, and crew apply to the trip. Destination and price apply to the selected booking.</p>' +
        '</div>',
      width: 980,
      showCancelButton: true,
      confirmButtonText: 'Save Changes',
      confirmButtonColor: '#696cff',
      focusConfirm: false,
      didOpen: function () {
        populateTripBookingFields(firstBooking);
        initTripEditDestinationMap(firstBooking);
        $('#editTripBooking').on('change', function () {
          const booking = getTripBookingByID(trip, $(this).val());
          populateTripBookingFields(booking);
          setEditTripDestinationMarker(booking.destination.latitude, booking.destination.longitude, true);
        });
        $('#editTripFuelPrice, #editTripTruck').on('input change', function () {
          lookupTripEditTariff(trip);
        });
        $('#editTripPrice').on('input change', function () {
          if (!$(this).data('settingTariffPrice')) {
            $(this).data('tariffAutofilled', false);
          }
        });
        $('.edit-trip-destination-field').on('input', function () {
          lookupTripEditTariff(trip);
        });
      },
      didClose: function () {
        if (editTripMap) {
          editTripMap.remove();
          editTripMap = null;
          editTripMarker = null;
        }
      },
      preConfirm: function () {
        const pickupDateTime = $('#editTripPickupDateTime').val();
        const nextStatus = $('#editTripStatus').val();
        const nextTruckID = $('#editTripTruck').val();
        const nextDriverID = $('#editTripDriver').val();
        const nextAssistantIDs = $('#editTripAssistants').val() || [];
        const bookingID = $('#editTripBooking').val();
        const price = $('#editTripPrice').val();
        const destinationLatitude = $('#editTripDestinationLatitude').val();
        const destinationLongitude = $('#editTripDestinationLongitude').val();

        if (!pickupDateTime || !nextStatus || !nextTruckID || !nextDriverID || nextAssistantIDs.length < 2) {
          Swal.showValidationMessage('Schedule, status, truck, driver, and at least two assistants are required.');
          return false;
        }

        if (nextAssistantIDs.indexOf(nextDriverID) !== -1) {
          Swal.showValidationMessage('Driver cannot also be selected as an assistant.');
          return false;
        }

        if (!bookingID || !price || !isValidTripCoordinate([destinationLatitude, destinationLongitude])) {
          Swal.showValidationMessage('Select a booking, enter price, and pin a valid destination inside Negros.');
          return false;
        }

        return {
          tripID: trip.tripID,
          pickupDateTime: pickupDateTime.replace('T', ' '),
          status: nextStatus,
          truckID: nextTruckID,
          driverID: nextDriverID,
          assistantIDs: JSON.stringify(nextAssistantIDs),
          bookingID: bookingID,
          price: price,
          fuelPrice: $('#editTripFuelPrice').val(),
          destinationProvince: $('#editTripDestinationProvince').val(),
          destinationCity: $('#editTripDestinationCity').val(),
          destinationBarangay: $('#editTripDestinationBarangay').val(),
          destinationStreet: $('#editTripDestinationStreet').val(),
          destinationDescription: $('#editTripDestinationDescription').val(),
          destinationLatitude: destinationLatitude,
          destinationLongitude: destinationLongitude
        };
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        saveTripInfo(result.value);
      }
    });
  }

  function buildBookingOptions(bookings) {
    return bookings.map(function (booking) {
      return '<option value="' + escapeAttr(booking.bookingID) + '">Booking #' + escapeHtml(booking.bookingID) + ' - ' + escapeHtml(booking.customerName || '-') + '</option>';
    }).join('');
  }

  function getTripBookingByID(trip, bookingID) {
    return (trip.bookings || []).find(function (booking) {
      return String(booking.bookingID) === String(bookingID);
    }) || (trip.bookings || [])[0] || {};
  }

  function populateTripBookingFields(booking) {
    const destination = booking.destination || {};
    $('#editTripPrice').val(booking.price || '');
    $('#editTripDestinationProvince').val(destination.province || '');
    $('#editTripDestinationCity').val(destination.city || '');
    $('#editTripDestinationBarangay').val(destination.barangay || '');
    $('#editTripDestinationStreet').val(destination.street || '');
    $('#editTripDestinationDescription').val(destination.description || '');
    $('#editTripDestinationLatitude').val(destination.latitude || '');
    $('#editTripDestinationLongitude').val(destination.longitude || '');
    $('#editTripTariffHint').text('Fuel pump is used to recalculate tariff price when available.');
  }

  function initTripEditDestinationMap(booking) {
    if (typeof L === 'undefined' || !document.getElementById('editTripDestinationMap')) {
      return;
    }

    if (editTripMap) {
      editTripMap.remove();
      editTripMap = null;
      editTripMarker = null;
    }

    const destination = booking.destination || {};
    const lat = Number(destination.latitude || 10.6765);
    const lng = Number(destination.longitude || 122.9509);

    editTripMap = L.map('editTripDestinationMap').setView([lat, lng], isValidTripCoordinate([lat, lng]) ? 14 : 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(editTripMap);

    editTripMap.on('click', function (event) {
      setEditTripDestinationMarker(event.latlng.lat, event.latlng.lng, false);
      reverseTripEditDestination(event.latlng.lat, event.latlng.lng);
    });

    setEditTripDestinationMarker(lat, lng, true);

    setTimeout(function () {
      editTripMap.invalidateSize();
    }, 200);
  }

  function setEditTripDestinationMarker(lat, lng, moveMap) {
    const latNum = Number(lat);
    const lngNum = Number(lng);

    if (!isValidTripCoordinate([latNum, lngNum]) || !editTripMap) {
      return;
    }

    const latlng = L.latLng(latNum, lngNum);
    if (!editTripMarker) {
      editTripMarker = L.marker(latlng, { draggable: true }).addTo(editTripMap);
      editTripMarker.on('dragend', function () {
        const position = editTripMarker.getLatLng();
        setEditTripDestinationMarker(position.lat, position.lng, false);
        reverseTripEditDestination(position.lat, position.lng);
      });
    } else {
      editTripMarker.setLatLng(latlng);
    }

    $('#editTripDestinationLatitude').val(latNum.toFixed(8));
    $('#editTripDestinationLongitude').val(lngNum.toFixed(8));
    if (moveMap) {
      editTripMap.setView(latlng, Math.max(editTripMap.getZoom(), 14));
    }
    lookupTripEditTariff(getTripByID(selectedTripID));
  }

  function reverseTripEditDestination(lat, lng) {
    const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' +
      encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&addressdetails=1';

    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data || !data.address) return;
        const address = data.address;
        $('#editTripDestinationProvince').val(address.state || address.region || address.province || '');
        $('#editTripDestinationCity').val(address.city || address.town || address.municipality || address.county || '');
        $('#editTripDestinationBarangay').val(address.suburb || address.village || address.neighbourhood || address.quarter || '');
        $('#editTripDestinationStreet').val([address.road, address.house_number].filter(Boolean).join(' '));
        $('#editTripDestinationDescription').val(data.display_name || '');
        lookupTripEditTariff(getTripByID(selectedTripID));
      })
      .catch(function () {});
  }

  function lookupTripEditTariff(trip) {
    const booking = getTripBookingByID(trip || {}, $('#editTripBooking').val());
    const fuelPrice = $('#editTripFuelPrice').val();
    const truckType = $('#editTripTruck option:selected').data('type') || '';
    const destinationText = [
      $('#editTripDestinationStreet').val(),
      $('#editTripDestinationBarangay').val(),
      $('#editTripDestinationCity').val(),
      $('#editTripDestinationProvince').val(),
      $('#editTripDestinationDescription').val()
    ].filter(Boolean).join(' ');

    if (!booking.customerID || !truckType || !destinationText.trim()) {
      return;
    }

    $.ajax({
      url: 'ajax/tariff_lookup.ajax.php',
      method: 'POST',
      dataType: 'json',
      data: {
        customerID: booking.customerID,
        truckType: truckType,
        destinationText: destinationText,
        fuelPrice: fuelPrice
      },
      success: function (response) {
        if (!response || response.status !== 'success') {
          clearAutofilledEditTripPrice();
          $('#editTripTariffHint').text('No matching tariff was found for the selected truck and destination. You can enter the price manually.');
          return;
        }

        const totalRate = Number(response.totalRate || response.baseRate || 0);
        $('#editTripPrice')
          .data('settingTariffPrice', true)
          .val(totalRate.toFixed(2))
          .data('tariffAutofilled', true)
          .data('settingTariffPrice', false);
        $('#editTripTariffHint').text('Tariff matched: ' + response.destination + ' = PHP ' + totalRate.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      }
    });
  }

  function clearAutofilledEditTripPrice() {
    const $price = $('#editTripPrice');

    if ($price.data('tariffAutofilled')) {
      $price.val('').data('tariffAutofilled', false);
    }
  }

  function getTripTruckID(trip) {
    const crew = trip.crew || [];
    const member = crew.find(function (item) { return item.truckID; });
    return member ? String(member.truckID) : '';
  }

  function getTripDriverID(trip) {
    const member = (trip.crew || []).find(function (item) { return item.role === 'driver'; });
    return member ? String(member.empID) : '';
  }

  function getTripAssistantIDs(trip) {
    return (trip.crew || [])
      .filter(function (item) { return item.role === 'assistant' && item.empID; })
      .map(function (item) { return String(item.empID); });
  }

  function buildTruckOptions(selectedID) {
    let html = '<option value="">Select truck</option>';
    (window.tripTruckOptions || []).forEach(function (truck) {
      const id = String(truck.id || '');
      const label = [truck.plateNumber, truck.type, truck.brand].filter(Boolean).join(' | ');
      html += '<option value="' + escapeAttr(id) + '" data-type="' + escapeAttr(truck.type || '') + '"' + (id === String(selectedID) ? ' selected' : '') + '>' + escapeHtml(label || id) + '</option>';
    });
    return html;
  }

  function buildEmployeeOptions(employees, selectedValue, placeholder) {
    const selectedValues = Array.isArray(selectedValue) ? selectedValue.map(String) : [String(selectedValue || '')];
    let html = '<option value="">' + escapeHtml(placeholder) + '</option>';

    employees.forEach(function (employee) {
      const id = String(employee.id || '');
      const name = [employee.empFName, employee.empLName].filter(Boolean).join(' ');
      html += '<option value="' + escapeAttr(id) + '"' + (selectedValues.indexOf(id) !== -1 ? ' selected' : '') + '>' + escapeHtml(name || id) + '</option>';
    });

    return html;
  }

  function saveTripInfo(data) {
    $.ajax({
      url: 'ajax/trip_update_record.ajax.php',
      method: 'POST',
      data: data,
      dataType: 'json',
      success: function (response) {
        if (response && response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Trip Updated',
            text: 'Trip information was updated successfully.',
            confirmButtonColor: '#696cff'
          }).then(function () {
            location.reload();
          });
          return;
        }

        showTripSaveError(response && response.message ? response.message : 'Unable to update trip.');
      },
      error: function () {
        showTripSaveError('Something went wrong while saving trip information.');
      }
    });
  }

  function showTripSaveError(message) {
    Swal.fire({
      icon: 'error',
      title: 'Update Failed',
      text: message,
      confirmButtonColor: '#696cff'
    });
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

      if (!isValidTripCoordinate(pickupLatLng) || !isValidTripCoordinate(destinationLatLng)) {
        return;
      }

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
    } else {
      map.setView([10.6765, 122.9509], 11);
      $('#tripMapStatus').text('This trip has invalid or missing map coordinates. Please check the booking location pins.');
    }

    setTimeout(function () {
      map.invalidateSize();
    }, 100);
  }

  function isValidTripCoordinate(latlng) {
    if (!Array.isArray(latlng) || latlng.length < 2) {
      return false;
    }

    const lat = Number(latlng[0]);
    const lng = Number(latlng[1]);

    return Number.isFinite(lat) &&
      Number.isFinite(lng) &&
      lat >= 9 &&
      lat <= 11.2 &&
      lng >= 122 &&
      lng <= 123.6;
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

  function toDateTimeLocalValue(value) {
    if (!value) {
      return '';
    }

    const normalized = String(value).replace(' ', 'T');
    return normalized.slice(0, 16);
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

  function escapeAttr(value) {
    return escapeHtml(value);
  }
});
