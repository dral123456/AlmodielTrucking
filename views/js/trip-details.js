$(document).ready(function () {
  const trips = Array.isArray(window.tripOverviewData) ? window.tripOverviewData : [];
  const canModifyTripInfo = window.tripCanModifyInfo === true;
  const canUpdateTripStatus = window.tripCanUpdateStatus === true;
  let selectedTripID = Number(sessionStorage.getItem('selectedTripID'));
  const trip = trips.find(t => Number(t.tripID) === selectedTripID);
  let map = null;
  let mapLayers = [];
  let tripDatePicker = null;
  let editTripMap = null;
  let editTripMarker = null;
  
  if (trip) {
    renderTripDetails(trip);
  } else {
    $('#tripDetails').html('<div class="text-muted text-center p-4">Trip not found.</div>');
  }


  initMap();
  bindEvents();

  function bindEvents() {
    $(document).on('click', '.trip-row', function () {
      const tripID = $(this).data('trip-id');
      sessionStorage.setItem('selectedTripID', tripID);
      window.location.href = 'trip-details';
    });

    $(document).on('click', '#tripModifyBtn', function () {
      if (!canModifyTripInfo) {
        return;
      }

      const trip = getTripByID(selectedTripID);
      if (trip) {
        showTripEditModal(trip);
      }
    });

    $(document).on('click', '.trip-status-action', function () {
      const button = $(this);
      const status = button.data('status');
      const tripID = selectedTripID;
      const labels = {
        'in-transit': 'start this delivery',
        'stopover': 'mark this trip as stopover',
        'completed': 'mark this trip as delivered'
      };

      if (!canUpdateTripStatus || !tripID) {
        return;
      }

      if (!window.confirm('Are you sure you want to ' + (labels[status] || 'update this trip') + '?')) {
        return;
      }

      button.prop('disabled', true);
      updateTripDeliveryStatus(tripID, status, button);
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

    const actionHtml = buildTripActionHtml(trip);

    $('#tripDetails').html(
      '<div class="trip-panel-heading mb-3">' +
        '<div class="d-flex align-items-center gap-2 flex-wrap">' +
          '<button class="btn btn-primary rounded-pill btn-sm" onclick="history.back()">'+
            '<i class="ri-arrow-left-line me-0"></i>'+
          '</button>' +
          '<h6 class="mb-0">Trip #' + escapeHtml(trip.tripID) + '</h6>' +
          '<p class="text-muted small mb-0">' + escapeHtml(formatDateTime(trip.firstPickupDateTime)) + ' | ' + escapeHtml(trip.bookingCount) + ' booking(s)</p>' +
        '</div>' +
        '<div class="d-flex align-items-center gap-2 flex-wrap justify-content-end" id="tripActionArea">' +
          '<span class="badge ' + status.className + '">' + status.label + '</span>' +
          actionHtml +
        '</div>' +
      '</div>' +
      '<div class="row g-3 mb-3">' +
        '<div class="col-12 col-lg-4"><div class="border rounded p-3 h-100"><span class="text-muted small d-block">Customer</span><strong>' + escapeHtml(customerText) + '</strong></div></div>' +
        '<div class="col-12 col-lg-4"><div class="border rounded p-3 h-100"><span class="text-muted small d-block">Trip Distance</span><strong>' + escapeHtml(formatKilometers(trip.totalDistanceKm)) + '</strong></div></div>' +
        '<div class="col-12 col-lg-4"><div class="border rounded p-3 h-100"><span class="text-muted small d-block">Crew</span><strong style="white-space: pre-line;">' + escapeHtml(crew) + '</strong></div></div>' +
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
    setTimeout(function () {
      if (map) {
        map.invalidateSize();
        renderTripMap(trip);
      }
    }, 100);
  }

  function buildTripActionHtml(trip) {
    if (canModifyTripInfo) {
      return '<button type="button" class="btn btn-sm btn-primary" id="tripModifyBtn"><i class="ri-edit-line me-1"></i> Modify</button>';
    }

    if (!canUpdateTripStatus) {
      return '';
    }

    let html = '';

    if (trip.status === 'pending') {
      html += '<button type="button" class="btn btn-sm btn-primary trip-status-action" data-status="in-transit"><i class="ri-play-circle-line me-1"></i> Start Delivery</button>';
    }

    if (trip.status !== 'completed') {
      html += '<button type="button" class="btn btn-sm btn-info trip-status-action" data-status="stopover"><i class="ri-map-pin-time-line me-1"></i> Stopover</button>';
      html += '<button type="button" class="btn btn-sm btn-success trip-status-action" data-status="completed"><i class="ri-check-double-line me-1"></i> Delivered</button>';
    }

    return html;
  }

  function showTripEditModal(trip) {
    const status = trip.status || 'pending';
    const truckID = getTripTruckID(trip);
    const driverID = getTripDriverID(trip);
    const assistantIDs = getTripAssistantIDs(trip);
    const firstBooking = (trip.bookings || [])[0] || {};

    Swal.fire({
      title: 'Modify Trip #' + trip.tripID,
      html: buildTripEditModalHtml(trip, status, truckID, driverID, assistantIDs),
      width: 1180,
      customClass: {
        popup: 'trip-edit-modal'
      },
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
          const destination = booking.destination || {};
          if (isValidTripCoordinate([destination.latitude, destination.longitude])) {
            setEditTripDestinationMarker(destination.latitude, destination.longitude, true);
          } else {
            clearEditTripDestinationMarker('This booking has no valid destination pin yet. Search or click the map inside Negros.');
            if (editTripMap) {
              editTripMap.setView([10.6765, 122.9509], 11);
            }
          }
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
        $('#editTripDestinationSearchBtn').on('click', function () {
          searchTripEditDestination();
        });
        $('#editTripDestinationSearch').on('keydown', function (event) {
          if (event.key === 'Enter') {
            event.preventDefault();
            searchTripEditDestination();
          }
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

        if (!bookingID) {
          Swal.showValidationMessage('Please choose which booking destination you want to modify.');
          return false;
        }

        const priceNumber = Number(price);
        if (price === '' || !Number.isFinite(priceNumber) || priceNumber < 0) {
          Swal.showValidationMessage('Please enter a valid booking price.');
          return false;
        }

        if (!isValidTripCoordinate([destinationLatitude, destinationLongitude])) {
          Swal.showValidationMessage('Please click the map or search an address to pin a valid destination inside Negros.');
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

  function buildTripEditModalHtml(trip, status, truckID, driverID, assistantIDs) {
    const bookingCount = (trip.bookings || []).length;

    return '' +
      '<div class="trip-edit-shell text-start">' +
        '<div class="trip-edit-summary">' +
          '<span><i class="ri-file-list-3-line me-1"></i>' + escapeHtml(bookingCount) + ' booking(s)</span>' +
          '<span><i class="ri-map-pin-line me-1"></i>Destination and price update the selected booking only</span>' +
        '</div>' +
        '<div class="trip-edit-grid trip-edit-main">' +
          '<div class="trip-edit-form">' +
            '<div class="trip-edit-card trip-edit-primary-card">' +
              '<div class="trip-edit-card-title"><span>1</span><div><strong>Destination And Price</strong><small>Choose the booking, search the address, then place the pin.</small></div></div>' +
              '<div class="row g-3">' +
                '<div class="col-12">' +
                  '<label class="form-label">Booking To Modify</label>' +
                  '<select class="form-select" id="editTripBooking">' + buildBookingOptions(trip.bookings || []) + '</select>' +
                '</div>' +
                '<div class="col-12">' +
                  '<label class="form-label">Search Destination</label>' +
                  '<div class="input-group">' +
                    '<input type="text" class="form-control" id="editTripDestinationSearch" placeholder="Search street, barangay, city">' +
                    '<button type="button" class="btn btn-primary" id="editTripDestinationSearchBtn"><i class="ri-search-line me-1"></i> Search</button>' +
                  '</div>' +
                '</div>' +
                '<div class="col-12 col-md-6">' +
                  '<label class="form-label">Fuel Pump Price</label>' +
                  '<input type="number" min="0" step="0.01" class="form-control" id="editTripFuelPrice" placeholder="e.g. 76">' +
                '</div>' +
                '<div class="col-12 col-md-6">' +
                  '<label class="form-label">Booking Price</label>' +
                  '<input type="number" min="0" step="0.01" class="form-control" id="editTripPrice">' +
                  '<div class="form-text" id="editTripTariffHint">Fuel pump is used to recalculate tariff price when available.</div>' +
                '</div>' +
              '</div>' +
            '</div>' +
            '<div class="trip-edit-card">' +
              '<div class="trip-edit-card-title"><span>2</span><div><strong>Address Details</strong><small>Saved with the selected destination.</small></div></div>' +
              '<div class="row g-3">' +
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
              '</div>' +
            '</div>' +
            '<details class="trip-edit-card trip-edit-details">' +
              '<summary><span>3</span><div><strong>Trip Schedule And Crew</strong><small>Optional trip-wide details. Leave as-is if you only want to update destination.</small></div><i class="ri-arrow-down-s-line"></i></summary>' +
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
                '<div class="col-12 col-lg-6">' +
                  '<label class="form-label">Driver</label>' +
                  '<select class="form-select" id="editTripDriver">' + buildEmployeeOptions(window.tripDriverOptions || [], driverID, 'Select driver') + '</select>' +
                '</div>' +
                '<div class="col-12 col-lg-6">' +
                  '<label class="form-label">Assistants</label>' +
                  '<select class="form-select" id="editTripAssistants" multiple size="4">' + buildEmployeeOptions(window.tripAssistantOptions || [], assistantIDs, 'Select assistants') + '</select>' +
                  '<div class="form-text">Select at least two assistants.</div>' +
                '</div>' +
              '</div>' +
            '</details>' +
          '</div>' +
          '<div class="trip-edit-map-card">' +
            '<div class="trip-edit-map-heading">' +
              '<div><strong>Destination Pin</strong><small id="editTripMapStatus">Choose a booking to load its destination pin.</small></div>' +
              '<span class="badge bg-label-primary" id="editTripCoordinateText">No pin selected</span>' +
            '</div>' +
            '<div id="editTripDestinationMap"></div>' +
            '<div class="trip-edit-map-help"><i class="ri-information-line me-1"></i> Click the map to move the pin, or drag the marker for a precise destination.</div>' +
            '<input type="hidden" id="editTripDestinationLatitude">' +
            '<input type="hidden" id="editTripDestinationLongitude">' +
          '</div>' +
        '</div>' +
      '</div>';
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
    const price = booking.price !== undefined && booking.price !== null ? booking.price : '';
    const hasValidDestination = isValidTripCoordinate([destination.latitude, destination.longitude]);

    $('#editTripPrice').val(price);
    $('#editTripDestinationProvince').val(destination.province || '');
    $('#editTripDestinationCity').val(destination.city || '');
    $('#editTripDestinationBarangay').val(destination.barangay || '');
    $('#editTripDestinationStreet').val(destination.street || '');
    $('#editTripDestinationDescription').val(destination.description || '');
    $('#editTripDestinationLatitude').val(hasValidDestination ? destination.latitude : '');
    $('#editTripDestinationLongitude').val(hasValidDestination ? destination.longitude : '');
    $('#editTripDestinationSearch').val(destination.address || destination.description || '');
    $('#editTripTariffHint').text('Fuel pump is used to recalculate tariff price when available.');
    updateEditTripCoordinateText(destination.latitude, destination.longitude);
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
    const hasValidDestination = isValidTripCoordinate([destination.latitude, destination.longitude]);
    const lat = hasValidDestination ? Number(destination.latitude) : 10.6765;
    const lng = hasValidDestination ? Number(destination.longitude) : 122.9509;

    editTripMap = L.map('editTripDestinationMap').setView([lat, lng], hasValidDestination ? 14 : 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(editTripMap);

    editTripMap.on('click', function (event) {
      setEditTripDestinationMarker(event.latlng.lat, event.latlng.lng, false);
      reverseTripEditDestination(event.latlng.lat, event.latlng.lng);
    });

    if (hasValidDestination) {
      setEditTripDestinationMarker(lat, lng, true);
    } else {
      clearEditTripDestinationMarker('No valid destination pin yet. Search or click the map inside Negros.');
    }

    setTimeout(function () {
      editTripMap.invalidateSize();
    }, 200);
  }

  function setEditTripDestinationMarker(lat, lng, moveMap) {
    const latNum = Number(lat);
    const lngNum = Number(lng);

    if (!isValidTripCoordinate([latNum, lngNum]) || !editTripMap) {
      setEditTripMapStatus('That pin is outside the supported Negros area. Please choose another point.');
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
    updateEditTripCoordinateText(latNum, lngNum);
    setEditTripMapStatus('Destination pin ready. Drag it or click another point to adjust.');
    if (moveMap) {
      editTripMap.setView(latlng, Math.max(editTripMap.getZoom(), 14));
    }
    lookupTripEditTariff(getTripByID(selectedTripID));
  }

  function clearEditTripDestinationMarker(message) {
    if (editTripMarker && editTripMap) {
      editTripMap.removeLayer(editTripMarker);
      editTripMarker = null;
    }

    $('#editTripDestinationLatitude').val('');
    $('#editTripDestinationLongitude').val('');
    $('#editTripCoordinateText').text('No pin selected');
    setEditTripMapStatus(message || 'No valid destination pin yet.');
  }

  function updateEditTripCoordinateText(lat, lng) {
    if (!isValidTripCoordinate([lat, lng])) {
      $('#editTripCoordinateText').text('No pin selected');
      return;
    }

    $('#editTripCoordinateText').text(Number(lat).toFixed(6) + ', ' + Number(lng).toFixed(6));
  }

  function setEditTripMapStatus(message) {
    $('#editTripMapStatus').text(message);
  }

  function searchTripEditDestination() {
    const typedSearch = ($('#editTripDestinationSearch').val() || '').trim();
    const fieldSearch = [
      $('#editTripDestinationStreet').val(),
      $('#editTripDestinationBarangay').val(),
      $('#editTripDestinationCity').val(),
      $('#editTripDestinationProvince').val()
    ].filter(Boolean).join(', ');
    const query = typedSearch || fieldSearch;

    if (!query) {
      setEditTripMapStatus('Type an address first, then search.');
      return;
    }

    const normalizedQuery = /philippines/i.test(query) ? query : query + ', Negros Occidental, Philippines';
    const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=ph&addressdetails=1&q=' + encodeURIComponent(normalizedQuery);

    setEditTripMapStatus('Searching destination address...');
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (response) { return response.ok ? response.json() : []; })
      .then(function (results) {
        const match = Array.isArray(results) && results.length ? results[0] : null;

        if (!match || !isValidTripCoordinate([match.lat, match.lon])) {
          setEditTripMapStatus('No valid Negros destination found. Try a more specific street, barangay, or city.');
          return;
        }

        setEditTripDestinationMarker(match.lat, match.lon, true);
        reverseTripEditDestination(match.lat, match.lon);
      })
      .catch(function () {
        setEditTripMapStatus('Address search failed. You can still click the map to set the pin.');
      });
  }

  function reverseTripEditDestination(lat, lng) {
    const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' +
      encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&addressdetails=1';

    setEditTripMapStatus('Loading address for selected pin...');
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data || !data.address) {
          setEditTripMapStatus('Destination pin ready, but no address was found. You can type the address manually.');
          return;
        }
        const address = data.address;
        $('#editTripDestinationProvince').val(address.state || address.region || address.province || '');
        $('#editTripDestinationCity').val(address.city || address.town || address.municipality || address.county || '');
        $('#editTripDestinationBarangay').val(address.suburb || address.village || address.neighbourhood || address.quarter || '');
        $('#editTripDestinationStreet').val([address.road, address.house_number].filter(Boolean).join(' '));
        $('#editTripDestinationDescription').val(data.display_name || '');
        $('#editTripDestinationSearch').val(data.display_name || '');
        setEditTripMapStatus('Address loaded for the selected destination pin.');
        lookupTripEditTariff(getTripByID(selectedTripID));
      })
      .catch(function () {
        setEditTripMapStatus('Destination pin ready, but address lookup failed. You can type the address manually.');
      });
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
      console.warn('renderTripMap: no map or no trip', { map, trip });
      return;
    }

    clearMap();
    const bounds = [];

    (trip.bookings || []).forEach(function (booking, index) {
      const pickupLatLng = [booking.pickup.latitude, booking.pickup.longitude];
      const destinationLatLng = [booking.destination.latitude, booking.destination.longitude];
      console.log('booking coords', pickupLatLng, destinationLatLng,
      isValidTripCoordinate(pickupLatLng), isValidTripCoordinate(destinationLatLng));

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
      return member.role.charAt(0).toUpperCase() + member.role.slice(1) + ': ' + name;
    }).join('\n');
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
