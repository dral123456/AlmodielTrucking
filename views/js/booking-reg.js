$(document).ready(function () {
  let map;
  let pickupMarker = null;
  let destinationMarker = null;
  let currentStep = 0;
  const totalSteps = 4;
  let NEGROS_BOUNDS = null;
  // Track locationIDs chosen from existing DB records (skip re-saving)
  let pickedPickupLocationID    = null;
  let pickedDestinationLocationID = null;

  let pickupIcon     = null;
  let destinationIcon = null;

  initMap();
  updateStepper();
  syncAssistantOptions();
  initLocationSearch('pickup');
  initLocationSearch('destination');

  $(document).on('change', 'input[name="bookingMapMode"]', updateMapStatus);

  $(document).on('change', '#bookingTruck', function () {
    applyTruckDefaultCrew($(this).val());
    lookupTariffPrice();
  });

  $(document).on('change', '#bookingCustomer', function () {
    applyCompanyWarehousePickup();
    lookupTariffPrice();
  });

  $(document).on('input change', '#bookingFuelPrice', lookupTariffPrice);

  $(document).on('change', '#bookingDriver, .booking-assistant', function () {
    syncAssistantOptions();
  });

  $(document).on('click', '#bookingAddAssistant', function () {
    addAssistantSelect('');
    syncAssistantOptions();
  });

  $(document).on('click', '.booking-remove-assistant', function () {
    $(this).closest('.booking-assistant-item').remove();
    syncAssistantOptions();
  });

  $(document).on('click', '.booking-step-pill', function () {
    const targetStep = Number($(this).data('step'));
    if (targetStep <= currentStep) { currentStep = targetStep; updateStepper(); return; }
    for (let step = currentStep; step < targetStep; step++) {
      const missing = validateStep(step);
      if (missing.length > 0) { showMissingModal(missing); return; }
    }
    currentStep = targetStep;
    updateStepper();
  });

  $(document).on('click', '#bookingBtnNext', function () {
    const missing = validateStep(currentStep);
    if (missing.length > 0) { showMissingModal(missing); return; }
    if (currentStep < totalSteps - 1) { currentStep++; updateStepper(); }
  });

  $(document).on('click', '#bookingBtnPrev', function () {
    if (currentStep > 0) { currentStep--; updateStepper(); }
  });

  $(document).on('click', '#toggleSidebar', function () {
    setTimeout(function () { if (map) map.invalidateSize(); }, 250);
  });

  $(window).on('resize', function () { if (map) map.invalidateSize(); });

  // Clear autofill flag when user manually edits address fields
  $(document).on('input',
    '#pickupProvince, #pickupCity, #pickupBarangay, #pickupStreet, #pickupDescription, ' +
    '#destinationProvince, #destinationCity, #destinationBarangay, #destinationStreet, #destinationDescription',
    function () {
      $(this).data('autofilled', false);
      if (this.id.indexOf('destination') === 0) {
        lookupTariffPrice();
      }
    }
  );

  $(document).on('click', '#bookingBtnReset', function () {
    $('#bookingCustomer, #bookingPickupDateTime, #bookingPrice, #bookingFuelPrice, #bookingTruck, #bookingDriver').val('');
    $('#bookingAssistantList .booking-assistant-item').slice(2).remove();
    $('.booking-assistant').val('');
    $('#bookingCargoList .booking-cargo-item').slice(1).remove();
    $('#bookingCargoList .cargo-type, #bookingCargoList .cargo-quantity, #cargoCondition, #cargoDescription, #cargoSpecialHandling').val('');
    $('#pickupProvince, #pickupCity, #pickupBarangay, #pickupStreet, #pickupDescription, #pickupLatitude, #pickupLongitude').val('');
    $('#destinationProvince, #destinationCity, #destinationBarangay, #destinationStreet, #destinationDescription, #destinationLatitude, #destinationLongitude').val('');
    setPickupLocked(false);
    $('.is-invalid').removeClass('is-invalid');
    if (pickupMarker)      { map.removeLayer(pickupMarker);      pickupMarker      = null; }
    if (destinationMarker) { map.removeLayer(destinationMarker); destinationMarker = null; }
    $('#pickupCoordinateText').text('Not pinned');
    $('#destinationCoordinateText').text('Not pinned');
    $('#mapModePickup').prop('checked', true);
    pickedPickupLocationID      = null;
    pickedDestinationLocationID = null;
    currentStep = 0;
    updateStepper();
    updateMapStatus();
    syncAssistantOptions();
  });

  $(document).on('click', '#bookingBtnRegister', function () {
    const missing = validateInputs();
    if (missing.length > 0) { showMissingModal(missing); return; }
    showConfirmModal();
  });

  // ─── Location search with local-first suggestions ──────────────────────────
  // Each map panel needs a search input (#pickupMapSearch / #destinationMapSearch)
  // and a suggestions container (#pickupMapSuggestions / #destinationMapSuggestions).
  function initLocationSearch(type) {
    const searchId     = '#' + type + 'MapSearch';
    const suggestId    = '#' + type + 'MapSuggestions';
    const searchBtnId  = '#' + type + 'MapSearchBtn';
    let debounceTimer  = null;

    // Live suggestions while typing
    $(document).on('input', searchId, function () {
      clearTimeout(debounceTimer);
      const query = $(this).val().trim();
      if (query.length < 2) { $(suggestId).hide().empty(); return; }

      debounceTimer = setTimeout(function () {
        fetchLocalSuggestions(query, suggestId, type);
      }, 280);
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function (e) {
      if (!$(e.target).closest(searchId + ', ' + suggestId).length) {
        $(suggestId).hide();
      }
    });

    // Search button / Enter: local first, fall back to Nominatim
    $(document).on('click', searchBtnId, function () {
      doSearch(type);
    });

    $(document).on('keydown', searchId, function (e) {
      if (e.key === 'Enter') { e.preventDefault(); doSearch(type); }
    });
  }

  function fetchLocalSuggestions(query, suggestId, type) {
    $.getJSON('ajax/location_search.ajax.php', { q: query })
      .done(function (results) {
        const $box = $(suggestId);
        $box.empty();

        if (!results || !results.length) { $box.hide(); return; }

        results.forEach(function (loc) {
          const $item = $('<div class="location-suggestion-item"></div>').text(loc.label);
          $item.on('click', function () {
            applyLocationSuggestion(type, loc);
            $box.hide().empty();
            $('#' + type + 'MapSearch').val(loc.label);
          });
          $box.append($item);
        });

        $box.show();
      })
      .fail(function () { /* silently ignore, user can still search Nominatim */ });
  }

  function applyLocationSuggestion(type, loc) {
    // Fill address fields
    setAddressFields(type, {
      province:    loc.province,
      city:        loc.city,
      barangay:    loc.barangay,
      street:      loc.street,
      description: loc.description,
    });

    // Pin the map
    const latlng = L.latLng(loc.lat, loc.lng);
    if (type === 'pickup') {
      if (!pickupMarker) {
        pickupMarker = L.marker(latlng, { draggable: true, icon: pickupIcon }).addTo(map);
        pickupMarker.on('dragend', function () {
          const pos = pickupMarker.getLatLng();
          setPickupCoordinates(pos.lat, pos.lng);
          fillAddressFromPin('pickup', pos.lat, pos.lng);
          pickedPickupLocationID = null; // user moved pin, no longer a known location
        });
      } else {
        pickupMarker.setLatLng(latlng);
      }
      setPickupCoordinates(loc.lat, loc.lng);
      pickedPickupLocationID = loc.locationID;  // reuse this ID on save
    } else {
      if (!destinationMarker) {
        destinationMarker = L.marker(latlng, { draggable: true, icon: destinationIcon }).addTo(map);
        destinationMarker.on('dragend', function () {
          const pos = destinationMarker.getLatLng();
          if (!isInNegros(pos)) {
            destinationMarker.setLatLng(L.latLng(
              Math.min(Math.max(pos.lat, 9.0), 11.0),
              Math.min(Math.max(pos.lng, 122.2), 123.4)
            ));
            $('#bookingMapStatus').text('Pin snapped back — must stay within Negros Island.');
            return;
          }
          setDestinationCoordinates(pos.lat, pos.lng);
          fillAddressFromPin('destination', pos.lat, pos.lng);
          pickedDestinationLocationID = null;
        });
      } else {
        destinationMarker.setLatLng(latlng);
      }
      setDestinationCoordinates(loc.lat, loc.lng);
      pickedDestinationLocationID = loc.locationID;
      lookupTariffPrice();
    }

    map.setView(latlng, Math.max(map.getZoom(), 15));
    updateMapStatus();
  }

  function setAddressFields(type, addr) {
    $('#' + type + 'Province').val(addr.province    || '');
    $('#' + type + 'City').val(addr.city             || '');
    $('#' + type + 'Barangay').val(addr.barangay     || '');
    $('#' + type + 'Street').val(addr.street         || '');
    $('#' + type + 'Description').val(addr.description || '');
    // Mark as autofilled so map drag can overwrite them
    ['Province','City','Barangay','Street','Description'].forEach(function (f) {
      $('#' + type + f).data('autofilled', true);
    });
  }

  function doSearch(type) {
    const searchId    = '#' + type + 'MapSearch';
    const suggestId   = '#' + type + 'MapSuggestions';
    const searchBtnId = '#' + type + 'MapSearchBtn';
    const query = $(searchId).val().trim();

    if (!query) { $(searchId).addClass('is-invalid'); return; }
    $(searchId).removeClass('is-invalid');
    $(suggestId).hide().empty();

    // 1. Try local DB first
    $.getJSON('ajax/location_search.ajax.php', { q: query })
      .done(function (results) {
        if (results && results.length > 0) {
          // Best local match — apply it directly
          applyLocationSuggestion(type, results[0]);
          return;
        }
        // 2. No local result — fall through to Nominatim
        nominatimSearch(type, query, searchBtnId);
      })
      .fail(function () {
        nominatimSearch(type, query, searchBtnId);
      });
  }

  function nominatimSearch(type, query, searchBtnId) {
    $(searchBtnId).prop('disabled', true)
      .html('<span class="spinner-border spinner-border-sm me-1"></span> Searching');

    const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&q=' +
      encodeURIComponent(query);

    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (results) {
        if (!Array.isArray(results) || !results.length) {
          $('#bookingMapStatus').text('No location found. Try a more specific address.');
          return;
        }
        const result  = results[0];
        const latlng  = { lat: Number(result.lat), lng: Number(result.lon) };
        if (!Number.isFinite(latlng.lat) || !Number.isFinite(latlng.lng)) return;

        if (!isInNegros(L.latLng(latlng.lat, latlng.lng))) {
          $('#bookingMapStatus').text('That location is outside Negros Island. Please search within Negros.');
          return;
        }

        setActiveMarkerAt(type, latlng);
        map.setView(L.latLng(latlng.lat, latlng.lng), 16);
      })
      .catch(function () {
        $('#bookingMapStatus').text('Search failed. Check your connection or click the map manually.');
      })
      .finally(function () {
        $(searchBtnId).prop('disabled', false).html('Search');
      });
  }

  // ─── Map ────────────────────────────────────────────────────────────────────
  // ─── Map ────────────────────────────────────────────────────────────────────
  // Negros Island bounding box — initialised inside initMap() once L is ready

  function isInNegros(latlng) {
    if (!NEGROS_BOUNDS) return true; // if not yet set, allow (shouldn't happen)
    return NEGROS_BOUNDS.contains(latlng);
  }

  function initMap() {
    if (typeof L === 'undefined' || !document.getElementById('bookingMap')) return;

    NEGROS_BOUNDS = L.latLngBounds(   // ← no 'let' here
      L.latLng(9.0,  122.2),
      L.latLng(11.0, 123.4)
    );
    pickupIcon     = createMarkerIcon('primary');
    destinationIcon = createMarkerIcon('danger');

    map = L.map('bookingMap', {
      maxBounds: NEGROS_BOUNDS,
      maxBoundsViscosity: 1.0,
      minZoom: 9
    }).setView([10.6765, 122.9509], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.on('click', function (event) {
      if (!isInNegros(event.latlng)) {
        Swal.fire({
          icon: 'warning',
          title: 'Out of range',
          text: 'Please select a location within Negros Island.',
          confirmButtonColor: '#696cff',
          timer: 2500,
          showConfirmButton: false
        });
        return;
      }
      setActiveMarker(event.latlng);
    });

    setTimeout(function () { map.invalidateSize(); }, 300);
    updateMapStatus();
  }

  function getSelectedCustomerOption() {
    const $option = $('#bookingCustomer option:selected');
    return $option.length && $option.val() ? $option : $();
  }

  function selectedCustomerIsCompany() {
    return getSelectedCustomerOption().data('type') === 'company';
  }

  function applyCompanyWarehousePickup() {
    const $option = getSelectedCustomerOption();
    const isCompany = selectedCustomerIsCompany();

    setPickupLocked(isCompany);

    if (!isCompany) {
      if (pickupMarker && map) { map.removeLayer(pickupMarker); pickupMarker = null; }
      $('#pickupProvince, #pickupCity, #pickupBarangay, #pickupStreet, #pickupDescription, #pickupLatitude, #pickupLongitude').val('');
      $('#pickupCoordinateText').text('Not pinned');
      pickedPickupLocationID = null;
      return;
    }

    const latitude  = Number($option.data('latitude'));
    const longitude = Number($option.data('longitude'));

    // Company customers already have a locationID — reuse it directly
    pickedPickupLocationID = $option.data('location-id') || null;

    $('#pickupProvince').val($option.data('province')    || '');
    $('#pickupCity').val($option.data('city')            || '');
    $('#pickupBarangay').val($option.data('barangay')    || '');
    $('#pickupStreet').val($option.data('street')        || '');
    $('#pickupDescription').val('Company warehouse pickup point');

    if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
      if (map && typeof L !== 'undefined') {
        const latlng = L.latLng(latitude, longitude);
        if (!pickupMarker) {
          pickupMarker = L.marker(latlng, { draggable: false, icon: pickupIcon }).addTo(map);
        } else {
          pickupMarker.setLatLng(latlng);
          if (pickupMarker.dragging) pickupMarker.dragging.disable();
        }
        map.setView(latlng, Math.max(map.getZoom(), 13));
      }
      setPickupCoordinates(latitude, longitude);
    }

    $('#mapModeDestination').prop('checked', true);
    updateMapStatus();
  }

  function setPickupLocked(locked) {
    $('#pickupProvince, #pickupCity, #pickupBarangay, #pickupStreet, #pickupDescription')
      .prop('readonly', locked).toggleClass('bg-light', locked);
    $('#mapModePickup').prop('disabled', locked);
    $('label[for="mapModePickup"]').toggleClass('disabled', locked);
    if (locked && $('#mapModePickup').is(':checked')) $('#mapModeDestination').prop('checked', true);
  }

  function updateStepper() {
    $('.booking-step').removeClass('active');
    $('.booking-step[data-step="' + currentStep + '"]').addClass('active');

    $('.booking-step-pill').each(function () {
      const step = Number($(this).data('step'));
      $(this).toggleClass('active', step === currentStep)
             .toggleClass('complete', step < currentStep);
      $(this).find('span').html(step < currentStep ? '<i class="ri-check-line"></i>' : String(step + 1));
    });

    $('#bookingStepProgress').css('width', ((currentStep / (totalSteps - 1)) * 100) + '%');
    $('#bookingBtnPrev').toggleClass('d-none', currentStep === 0);
    $('#bookingBtnNext').toggleClass('d-none', currentStep === totalSteps - 1);
    $('#bookingBtnRegister').toggleClass('d-none', currentStep !== totalSteps - 1);

    if (currentStep === 2 && map) setTimeout(function () { map.invalidateSize(); }, 150);
    if (currentStep === 3) updateReview();
  }

  function createMarkerIcon(type) {
    const color = type === 'danger' ? '#ff3e1d' : '#696cff';
    return L.divIcon({
      className: '',
      html: '<span style="display:block;width:18px;height:18px;border-radius:50%;background:' + color + ';border:3px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.35);"></span>',
      iconSize: [18, 18],
      iconAnchor: [9, 9]
    });
  }

  function applyTruckDefaultCrew(truckID) {
    const crew = (window.bookingTruckCrew && window.bookingTruckCrew[truckID]) ? window.bookingTruckCrew[truckID] : null;
    $('#bookingDriver').val(crew ? crew.driverID : '');
    $('#bookingAssistantList .booking-assistant-item').slice(2).remove();
    $('.booking-assistant').val('');
    if (crew && Array.isArray(crew.assistantIDs)) {
      crew.assistantIDs.forEach(function (assistantID, index) {
        if (index < 2) { $('.booking-assistant').eq(index).val(assistantID); return; }
        addAssistantSelect(assistantID);
      });
    }
    syncAssistantOptions();
  }

  function addAssistantSelect(value) {
    const $first = $('.booking-assistant').first();
    if (!$first.length) return;
    const $item  = $('<div class="col-12 col-md-6 mb-3 booking-assistant-item"></div>');
    const $group = $('<div class="input-group"></div>');
    const $select = $first.clone();
    $select.val(value || '').removeAttr('data-default-slot').removeClass('is-invalid');
    $group.append($select);
    $group.append(
      '<button class="btn btn-outline-danger booking-remove-assistant" type="button">' +
        '<i class="ri-close-line"></i>' +
      '</button>'
    );
    $item.append($group);
    $('#bookingAssistantList').append($item);
  }

  function getAssistantIDs() {
    return $('.booking-assistant').map(function () {
      return String($(this).val() || '').trim();
    }).get().filter(Boolean);
  }

  function syncAssistantOptions() {
    const selectedAssistants = getAssistantIDs();
    $('.booking-assistant option').prop('hidden', false).prop('disabled', false);
    $('.booking-assistant').each(function () {
      const currentValue = String($(this).val() || '');
      selectedAssistants.forEach(function (assistantID) {
        if (assistantID !== currentValue) {
          $(this).find('option[value="' + assistantID + '"]').prop('hidden', true).prop('disabled', true);
        }
      }, this);
    });
  }

  function getMapMode() {
    return $('input[name="bookingMapMode"]:checked').val();
  }

  function updateMapStatus() {
    const mode = getMapMode();
    const customerIsCompany = selectedCustomerIsCompany();

    if (customerIsCompany) {
      $('#bookingMapStatus').text('Company warehouse is fixed as pickup. Click the map to place the destination pin.');
      return;
    }
    $('#bookingMapStatus').text(
      mode === 'pickup'
        ? 'Click the map to place the pickup pin.'
        : 'Click the map to place the destination pin.'
    );
  }

  function setActiveMarker(latlng) {
    const mode = getMapMode();
    const customerIsCompany = selectedCustomerIsCompany();

    if (customerIsCompany && mode === 'pickup') {
      $('#mapModeDestination').prop('checked', true);
      updateMapStatus();
      return;
    }
    setActiveMarkerAt(mode === 'pickup' ? 'pickup' : 'destination', latlng);
    if (mode === 'pickup') { $('#mapModeDestination').prop('checked', true); updateMapStatus(); }
  }

  function setActiveMarkerAt(type, latlng) {
    const lat = latlng.lat.toFixed ? Number(latlng.lat).toFixed(8) : latlng.lat;
    const lng = latlng.lng.toFixed ? Number(latlng.lng).toFixed(8) : latlng.lng;
    const ll  = L.latLng(latlng.lat, latlng.lng);

    if (type === 'pickup') {
      if (!pickupMarker) {
        pickupMarker = L.marker(ll, { draggable: true, icon: pickupIcon }).addTo(map);
        pickupMarker.on('dragend', function () {
          const pos = pickupMarker.getLatLng();
          if (!isInNegros(pos)) {
            pickupMarker.setLatLng(L.latLng(
              Math.min(Math.max(pos.lat, 9.0), 11.0),
              Math.min(Math.max(pos.lng, 122.2), 123.4)
            ));
            $('#bookingMapStatus').text('Pin snapped back — must stay within Negros Island.');
            return;
          }
          setPickupCoordinates(pos.lat, pos.lng);
          fillAddressFromPin('pickup', pos.lat, pos.lng);
          pickedPickupLocationID = null; // user dragged, unknown location
        });
      } else {
        pickupMarker.setLatLng(ll);
      }
      setPickupCoordinates(lat, lng);
      fillAddressFromPin('pickup', lat, lng);
      pickedPickupLocationID = null; // fresh Nominatim result, not yet in DB
    } else {
      if (!destinationMarker) {
        destinationMarker = L.marker(ll, { draggable: true, icon: destinationIcon }).addTo(map);
        destinationMarker.on('dragend', function () {
          const pos = destinationMarker.getLatLng();
          setDestinationCoordinates(pos.lat, pos.lng);
          fillAddressFromPin('destination', pos.lat, pos.lng);
          pickedDestinationLocationID = null;
        });
      } else {
        destinationMarker.setLatLng(ll);
      }
      setDestinationCoordinates(lat, lng);
      fillAddressFromPin('destination', lat, lng);
      pickedDestinationLocationID = null;
    }
  }

  function setPickupCoordinates(lat, lng) {
    const fLat = Number(lat).toFixed(8);
    const fLng = Number(lng).toFixed(8);
    $('#pickupLatitude').val(fLat);
    $('#pickupLongitude').val(fLng);
    $('#pickupCoordinateText').text(fLat + ', ' + fLng);
    $('#pickupLatitude, #pickupLongitude').removeClass('is-invalid');
  }

  function setDestinationCoordinates(lat, lng) {
    const fLat = Number(lat).toFixed(8);
    const fLng = Number(lng).toFixed(8);
    $('#destinationLatitude').val(fLat);
    $('#destinationLongitude').val(fLng);
    $('#destinationCoordinateText').text(fLat + ', ' + fLng);
    $('#destinationLatitude, #destinationLongitude').removeClass('is-invalid');
    lookupTariffPrice();
  }

  function fillAddressFromPin(type, lat, lng) {
    const label = type === 'pickup' ? 'Pickup' : 'Destination';
    const url   = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' +
      encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&addressdetails=1';

    $('#bookingMapStatus').text('Looking up ' + label.toLowerCase() + ' address...');

    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || !data.address) { updateMapStatus(); return; }
        const a = data.address;
        setIfEmptyOrAutofilled(type + 'Province',    a.state || a.region || a.province || '');
        setIfEmptyOrAutofilled(type + 'City',        a.city  || a.town   || a.municipality || a.county || '');
        setIfEmptyOrAutofilled(type + 'Barangay',    a.suburb || a.village || a.neighbourhood || a.quarter || '');
        setIfEmptyOrAutofilled(type + 'Street',      [a.road, a.house_number].filter(Boolean).join(' '));
        setIfEmptyOrAutofilled(type + 'Description', data.display_name || '');
        $('#' + type + 'Province, #' + type + 'City, #' + type + 'Barangay, #' + type + 'Street').removeClass('is-invalid');
        updateMapStatus();
        if (type === 'destination') lookupTariffPrice();
      })
      .catch(function () { updateMapStatus(); });
  }

  function setIfEmptyOrAutofilled(id, value) {
    if (!value) return;
    const $el = $('#' + id);
    if (String($el.val() || '').trim() === '' || $el.data('autofilled') === true) {
      $el.val(value).data('autofilled', true);
    }
  }

  function lookupTariffPrice() {
    const customerID = $('#bookingCustomer').val();
    const $customer = getSelectedCustomerOption();
    const truckType = $('#bookingTruck option:selected').data('type') || '';
    const fuelPrice = $('#bookingFuelPrice').val();
    const destinationText = [
      $('#destinationStreet').val(),
      $('#destinationBarangay').val(),
      $('#destinationCity').val(),
      $('#destinationProvince').val(),
      $('#destinationDescription').val()
    ].filter(Boolean).join(' ');

    if (!customerID || !$customer.length || $customer.data('type') !== 'company' || !truckType || !destinationText.trim()) {
      $('#bookingTariffHint').text('Select company, truck, and destination to use tariff pricing.');
      return;
    }

    $('#bookingTariffHint').text('Checking tariff for this destination...');

    $.ajax({
      url: 'ajax/tariff_lookup.ajax.php',
      method: 'POST',
      dataType: 'json',
      data: {
        customerID: customerID,
        truckType: truckType,
        destinationText: destinationText,
        fuelPrice: fuelPrice
      },
      success: function (response) {
        if (!response || response.status !== 'success') {
          $('#bookingTariffHint').text('No tariff matched. You may enter the booking price manually.');
          return;
        }

        const totalRate = Number(response.totalRate || response.baseRate || 0);
        const fuelSubsidy = Number(response.fuelSubsidy || 0);
        $('#bookingPrice').val(totalRate.toFixed(2)).removeClass('is-invalid');
        const fuelBaseMin = Number(response.fuelBaseMin || 0);
        const fuelBaseMax = Number(response.fuelBaseMax || 0);
        const fuelBaseRange = fuelBaseMin > 0 && fuelBaseMax > 0 ? fuelBaseMin + '-' + fuelBaseMax : '';
        const fuelNote = fuelSubsidy > 0
          ? ' + fuel subsidy PHP ' + fuelSubsidy.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
          : ' + no fuel subsidy' + (fuelBaseRange ? ' because fuel is within base range ' + fuelBaseRange : '');

        $('#bookingTariffHint').text(
          'Tariff matched: ' + response.origin + ' to ' + response.destination +
          ' | ' + response.distanceKm + ' km | base PHP ' + Number(response.baseRate || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
          fuelNote +
          ' = PHP ' + totalRate.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        );
      },
      error: function () {
        $('#bookingTariffHint').text('Tariff lookup failed. You may enter the booking price manually.');
      }
    });
  }

  function validateInputs() {
    const allMissing = [];

    for (let step = 0; step < totalSteps - 1; step++) {
      allMissing.push(...validateStep(step));
    }

    checkFinalPrice(allMissing);

    return [...new Set(allMissing)];
  }

  function validateStep(step) {
    const missing = [];
    const check = (id, label) => {
      const $el = $('#' + id);
      const ok  = String($el.val() || '').trim() !== '';
      if (!ok) { missing.push(label); $el.addClass('is-invalid'); }
      else { $el.removeClass('is-invalid'); }
    };

    if (step === 0) {
      check('bookingCustomer', 'Customer');
      check('bookingTruck', 'Truck');
      check('bookingDriver', 'Driver');
      check('bookingPickupDateTime', 'Pickup Date & Time');

      const assistantIDs = getAssistantIDs();
      if (assistantIDs.length < 2) {
        missing.push('At least 2 assistants');
        $('.booking-assistant').each(function () { if (!$(this).val()) $(this).addClass('is-invalid'); });
      } else { $('.booking-assistant').removeClass('is-invalid'); }
      if (new Set(assistantIDs).size !== assistantIDs.length) {
        missing.push('Assistants must be different employees');
        $('.booking-assistant').addClass('is-invalid');
      }

    }

    if (step === 1) {
      let hasCompleteCargo = false;

      $('.booking-cargo-item').each(function () {
        const $type     = $(this).find('.cargo-type');
        const $quantity = $(this).find('.cargo-quantity');
        const type      = String($type.val() || '').trim();
        const rawQty    = String($quantity.val() || '').trim();
        const quantity  = Number(rawQty);
        const quantityIsValid = rawQty !== '' && !Number.isNaN(quantity) && Math.floor(quantity) === quantity && quantity >= 1;

        $type.toggleClass('is-invalid', type === '');
        $quantity.toggleClass('is-invalid', !quantityIsValid);
        if (type !== '' && quantityIsValid) {
          hasCompleteCargo = true;
        }
      });

      if (!hasCompleteCargo) {
        missing.push('At least 1 cargo item with type and quantity');
      }

      if ($('.booking-cargo-item .cargo-quantity.is-invalid').length) {
        missing.push('Each cargo quantity must be a whole number greater than 0');
      }
    }

    if (step === 2) {
      check('pickupLatitude',    'Pickup Map Pin');
      check('pickupLongitude',   'Pickup Map Pin');
      if (hasPinnedLocation('pickup')) {
        $('#pickupProvince, #pickupCity, #pickupBarangay, #pickupStreet').removeClass('is-invalid');
      } else {
        check('pickupProvince',  'Pickup Province');
        check('pickupCity',      'Pickup City');
        check('pickupBarangay',  'Pickup Barangay');
        check('pickupStreet',    'Pickup Street');
      }
      check('destinationProvince', 'Destination Province');
      check('destinationCity',     'Destination City');
      check('destinationBarangay', 'Destination Barangay');
      check('destinationStreet',   'Destination Street');
      check('destinationLatitude', 'Destination Map Pin');
      check('destinationLongitude', 'Destination Map Pin');
      check('bookingPrice', selectedCustomerIsCompany() ? 'Company tariff price' : 'Price');
      checkPriceValue(missing);
    }

    return [...new Set(missing)];
  }

  function hasPinnedLocation(prefix) {
    const lat = Number($('#' + prefix + 'Latitude').val());
    const lng = Number($('#' + prefix + 'Longitude').val());
    return Number.isFinite(lat) && Number.isFinite(lng);
  }

  function checkFinalPrice(missing) {
    if (String($('#bookingPrice').val() || '').trim() === '') {
      missing.push(selectedCustomerIsCompany() ? 'Company tariff price' : 'Price');
      $('#bookingPrice').addClass('is-invalid');

      if (selectedCustomerIsCompany()) {
        $('#bookingTariffHint').text('Select a matching destination and truck so the company tariff can fill the price.');
      }
      return;
    }

    checkPriceValue(missing);
  }

  function checkPriceValue(missing) {
    const price = Number($('#bookingPrice').val());
    if ($('#bookingPrice').val() !== '' && (Number.isNaN(price) || price < 0)) {
      missing.push('Price must be a valid number');
      $('#bookingPrice').addClass('is-invalid');
      return;
    }

    if ($('#bookingPrice').val() !== '') {
      $('#bookingPrice').removeClass('is-invalid');
    }
  }

  function updateReview() {
    const assistantNames = $('.booking-assistant').map(function () {
      return $(this).find('option:selected').text().trim();
    }).get().filter(Boolean).join(', ');

    $('#reviewCustomer').text($('#bookingCustomer option:selected').text().trim() || '-');
    $('#reviewTripSchedule').text('Generated on save / ' + ($('#bookingPickupDateTime').val() || '-'));
    $('#reviewCrew').text(
      ($('#bookingTruck option:selected').text().trim()  || '-') +
      ' / Driver: '     + ($('#bookingDriver option:selected').text().trim() || '-') +
      ' / Assistants: ' + (assistantNames || '-')
    );
    const cargoSummary = [];
    $('.booking-cargo-item').each(function () {
      const t = $(this).find('.cargo-type').val().trim();
      const q = $(this).find('.cargo-quantity').val().trim();
      if (t && q) cargoSummary.push(t + ' x ' + q);
    });
    $('#reviewCargo').text(cargoSummary.length ? cargoSummary.join(', ') : '-');
    $('#reviewPrice').text($('#bookingPrice').val() || '-');
    $('#reviewPickup').text(formatAddress('pickup')      + ' (' + ($('#pickupCoordinateText').text()      || '-') + ')');
    $('#reviewDestination').text(formatAddress('destination') + ' (' + ($('#destinationCoordinateText').text() || '-') + ')');
  }

  function formatAddress(prefix) {
    return [
      $('#' + prefix + 'Street').val(),
      $('#' + prefix + 'Barangay').val(),
      $('#' + prefix + 'City').val(),
      $('#' + prefix + 'Province').val()
    ].filter(Boolean).join(', ') || '-';
  }

  function showMissingModal(missing) {
    const listHtml = '<ul class="text-start mb-0 ps-3">' +
      missing.map(m => '<li>' + m + '</li>').join('') + '</ul>';
    Swal.fire({
      icon: 'warning', title: 'Missing Required Fields',
      html: '<p class="text-muted mb-2">Please review the following:</p>' + listHtml,
      confirmButtonText: 'OK', confirmButtonColor: '#696cff'
    });
  }

  function showConfirmModal() {
    const cargoConfirm = [];
    $('.booking-cargo-item').each(function () {
      const t = $(this).find('.cargo-type').val().trim();
      const q = $(this).find('.cargo-quantity').val().trim();
      if (t && q) cargoConfirm.push(t + ' (' + q + ')');
    });
    Swal.fire({
      icon: 'question', title: 'Confirm Booking',
      html:
        '<p class="mb-2">Please review the details before submitting:</p>' +
        '<div class="text-start bg-light rounded p-3">' +
          '<div><strong>Customer:</strong> '    + ($('#bookingCustomer option:selected').text().trim() || '-') + '</div>' +
          '<div><strong>Trip ID:</strong> Generated on save</div>' +
          '<div><strong>Truck:</strong> '       + ($('#bookingTruck option:selected').text().trim()    || '-') + '</div>' +
          '<div><strong>Driver:</strong> '      + ($('#bookingDriver option:selected').text().trim()   || '-') + '</div>' +
          '<div><strong>Cargo:</strong> ' + (cargoConfirm.join(', ') || '-') + '</div>' +
          '<div><strong>Pickup:</strong> '      + ($('#pickupCoordinateText').text()      || '-') + '</div>' +
          '<div><strong>Destination:</strong> ' + ($('#destinationCoordinateText').text() || '-') + '</div>' +
        '</div>',
      showCancelButton: true,
      confirmButtonText: '<i class="ri-check-line"></i> Yes, Save',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#696cff', cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) saveLocationsAndBooking();
    });
  }

  // ─── Save: locations first, then booking ────────────────────────────────────
  function saveLocationsAndBooking() {
    const pickupLocationID      = pickedPickupLocationID;
    const destinationLocationID = pickedDestinationLocationID;

    // If both locations are already known (selected from suggestions), skip saving
    if (pickupLocationID && destinationLocationID) {
      saveBooking(pickupLocationID, destinationLocationID);
      return;
    }

    // Save pickup location (or reuse nearby)
    const savePickup = pickupLocationID
      ? Promise.resolve(pickupLocationID)
      : saveOneLocation({
          province:    $('#pickupProvince').val(),
          city:        $('#pickupCity').val(),
          barangay:    $('#pickupBarangay').val(),
          street:      $('#pickupStreet').val(),
          description: $('#pickupDescription').val(),
          lat:         $('#pickupLatitude').val(),
          lng:         $('#pickupLongitude').val(),
        });

    // Save destination location (or reuse nearby)
    const saveDestination = destinationLocationID
      ? Promise.resolve(destinationLocationID)
      : saveOneLocation({
          province:    $('#destinationProvince').val(),
          city:        $('#destinationCity').val(),
          barangay:    $('#destinationBarangay').val(),
          street:      $('#destinationStreet').val(),
          description: $('#destinationDescription').val(),
          lat:         $('#destinationLatitude').val(),
          lng:         $('#destinationLongitude').val(),
        });

    Promise.all([savePickup, saveDestination])
      .then(function (ids) {
        const pID = ids[0];
        const dID = ids[1];
        if (!pID || !dID) {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save location. Please try again.', confirmButtonColor: '#696cff' });
          return;
        }
        saveBooking(pID, dID);
      })
      .catch(function () {
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Something went wrong while saving locations.', confirmButtonColor: '#696cff' });
      });
  }

  function saveOneLocation(data) {
    return new Promise(function (resolve, reject) {
      const fd = new FormData();
      Object.entries(data).forEach(function ([k, v]) { fd.append(k, v || ''); });

      $.ajax({
        url: 'ajax/location_save_record.ajax.php',
        method: 'POST',
        data: fd,
        cache: false, contentType: false, processData: false,
        dataType: 'json',
        success: function (res) {
          if (res.status === 'success' && res.locationID) resolve(res.locationID);
          else reject(new Error('location save failed'));
        },
        error: reject
      });
    });
  }

  function saveBooking(pickupLocationID, destinationLocationID) {
    const formData = new FormData();

    formData.append('customerID',            $('#bookingCustomer').val());
    formData.append('truckID',               $('#bookingTruck').val());
    formData.append('driverID',              $('#bookingDriver').val());
    formData.append('assistantIDs',          JSON.stringify(getAssistantIDs()));
    formData.append('pickupDateTime',        $('#bookingPickupDateTime').val().replace('T', ' '));
    formData.append('price',                 $('#bookingPrice').val());
    // Replace the single cargo appends with:
    const cargoItems = [];
    $('.booking-cargo-item').each(function () {
      const type = $(this).find('.cargo-type').val().trim();
      const qty  = $(this).find('.cargo-quantity').val().trim();
      if (type && qty) cargoItems.push({ cargoType: type, quantity: qty });
    });
    formData.append('cargoItems',        JSON.stringify(cargoItems));
    formData.append('cargoCondition',    $('#cargoCondition').val());
    formData.append('cargoDescription',  $('#cargoDescription').val());
    formData.append('cargoSpecialHandling', $('#cargoSpecialHandling').val());
    formData.append('pickupLocationID',      pickupLocationID);
    formData.append('destinationLocationID', destinationLocationID);

    $.ajax({
      url: 'ajax/booking_save_record.ajax.php',
      method: 'POST',
      data: formData,
      cache: false, contentType: false, processData: false,
      dataType: 'text',
      success: function (response) {
        const res = (response || '').trim();
        if (res === 'success') {
          Swal.fire({
            icon: 'success', title: 'Saved!', text: 'Booking saved successfully.', confirmButtonColor: '#696cff'
          }).then(() => { window.location = 'booking-reg'; });
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save booking.', confirmButtonColor: '#696cff' });
        }
      },
      error: function () {
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Something went wrong while saving.', confirmButtonColor: '#696cff' });
      }
    });
  }
});
