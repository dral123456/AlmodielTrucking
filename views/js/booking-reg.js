$(document).ready(function () {
  let map;
  let pickupMarker = null;
  let destinationMarker = null;
  let currentStep = 0;
  const totalSteps = 4;

  const pickupIcon = createMarkerIcon('primary');
  const destinationIcon = createMarkerIcon('danger');

  initMap();
  updateStepper();
  syncAssistantOptions();

  $(document).on('change', 'input[name="bookingMapMode"]', updateMapStatus);

  $(document).on('change', '#bookingTruck', function () {
    applyTruckDefaultCrew($(this).val());
  });

  $(document).on('change', '#bookingCustomer', function () {
    applyCompanyWarehousePickup();
  });

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

    if (targetStep <= currentStep) {
      currentStep = targetStep;
      updateStepper();
      return;
    }

    for (let step = currentStep; step < targetStep; step++) {
      const missing = validateStep(step);
      if (missing.length > 0) {
        showMissingModal(missing);
        return;
      }
    }

    currentStep = targetStep;
    updateStepper();
  });

  $(document).on('click', '#bookingBtnNext', function () {
    const missing = validateStep(currentStep);

    if (missing.length > 0) {
      showMissingModal(missing);
      return;
    }

    if (currentStep < totalSteps - 1) {
      currentStep++;
      updateStepper();
    }
  });

  $(document).on('click', '#bookingBtnPrev', function () {
    if (currentStep > 0) {
      currentStep--;
      updateStepper();
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

  $(document).on('input', '#pickupProvince, #pickupCity, #pickupBarangay, #pickupStreet, #pickupDescription, #destinationProvince, #destinationCity, #destinationBarangay, #destinationStreet, #destinationDescription', function () {
    $(this).data('autofilled', false);
  });

  $(document).on('click', '#bookingBtnReset', function () {
    $('#bookingCustomer, #bookingPickupDateTime, #bookingPrice, #bookingTruck, #bookingDriver').val('');
    $('#bookingAssistantList .booking-assistant-item').slice(2).remove();
    $('.booking-assistant').val('');
    $('#cargoType, #cargoQuantity, #cargoCondition, #cargoDescription, #cargoSpecialHandling').val('');
    $('#pickupProvince, #pickupCity, #pickupBarangay, #pickupStreet, #pickupDescription, #pickupLatitude, #pickupLongitude').val('');
    $('#destinationProvince, #destinationCity, #destinationBarangay, #destinationStreet, #destinationDescription, #destinationLatitude, #destinationLongitude').val('');
    setPickupLocked(false);
    $('.is-invalid').removeClass('is-invalid');

    if (pickupMarker) {
      map.removeLayer(pickupMarker);
      pickupMarker = null;
    }

    if (destinationMarker) {
      map.removeLayer(destinationMarker);
      destinationMarker = null;
    }

    $('#pickupCoordinateText').text('Not pinned');
    $('#destinationCoordinateText').text('Not pinned');
    $('#mapModePickup').prop('checked', true);
    currentStep = 0;
    updateStepper();
    updateMapStatus();
    syncAssistantOptions();
  });

  $(document).on('click', '#bookingBtnRegister', function () {
    const missing = validateInputs();

    if (missing.length > 0) {
      showMissingModal(missing);
      return;
    }

    showConfirmModal();
  });

  function initMap() {
    if (typeof L === 'undefined' || !document.getElementById('bookingMap')) {
      return;
    }

    map = L.map('bookingMap').setView([10.6765, 122.9509], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.on('click', function (event) {
      setActiveMarker(event.latlng);
    });

    setTimeout(function () {
      map.invalidateSize();
    }, 300);

    updateMapStatus();
  }

  function getSelectedCustomerOption() {
    const $option = $('#bookingCustomer option:selected');
    return $option.length && $option.val() ? $option : $();
  }

  function applyCompanyWarehousePickup() {
    const $option = getSelectedCustomerOption();
    const isCompany = $option.data('type') === 'company';

    setPickupLocked(isCompany);

    if (!isCompany) {
      if (pickupMarker && map) {
        map.removeLayer(pickupMarker);
        pickupMarker = null;
      }
      $('#pickupProvince, #pickupCity, #pickupBarangay, #pickupStreet, #pickupDescription, #pickupLatitude, #pickupLongitude').val('');
      $('#pickupCoordinateText').text('Not pinned');
      return;
    }

    const latitude = Number($option.data('latitude'));
    const longitude = Number($option.data('longitude'));

    $('#pickupProvince').val($option.data('province') || '');
    $('#pickupCity').val($option.data('city') || '');
    $('#pickupBarangay').val($option.data('barangay') || '');
    $('#pickupStreet').val($option.data('street') || '');
    $('#pickupDescription').val('Company warehouse pickup point');

    if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
      if (map && typeof L !== 'undefined') {
        const latlng = L.latLng(latitude, longitude);
        if (!pickupMarker) {
          pickupMarker = L.marker(latlng, { draggable: false, icon: pickupIcon }).addTo(map);
        } else {
          pickupMarker.setLatLng(latlng);
          if (pickupMarker.dragging) {
            pickupMarker.dragging.disable();
          }
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
      .prop('readonly', locked)
      .toggleClass('bg-light', locked);
    $('#mapModePickup').prop('disabled', locked);
    $('label[for="mapModePickup"]').toggleClass('disabled', locked);

    if (locked && $('#mapModePickup').is(':checked')) {
      $('#mapModeDestination').prop('checked', true);
    }
  }

  function updateStepper() {
    $('.booking-step').removeClass('active');
    $('.booking-step[data-step="' + currentStep + '"]').addClass('active');

    $('.booking-step-pill').each(function () {
      const step = Number($(this).data('step'));
      $(this).toggleClass('active', step === currentStep);
      $(this).toggleClass('complete', step < currentStep);
      $(this).find('span').html(step < currentStep ? '<i class="ri-check-line"></i>' : String(step + 1));
    });

    $('#bookingStepProgress').css('width', ((currentStep / (totalSteps - 1)) * 100) + '%');
    $('#bookingBtnPrev').toggleClass('d-none', currentStep === 0);
    $('#bookingBtnNext').toggleClass('d-none', currentStep === totalSteps - 1);
    $('#bookingBtnRegister').toggleClass('d-none', currentStep !== totalSteps - 1);

    if (currentStep === 2 && map) {
      setTimeout(function () {
        map.invalidateSize();
      }, 150);
    }

    if (currentStep === 3) {
      updateReview();
    }
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
        if (index < 2) {
          $('.booking-assistant').eq(index).val(assistantID);
          return;
        }

        addAssistantSelect(assistantID);
      });
    }

    syncAssistantOptions();
  }

  function addAssistantSelect(value) {
    const $first = $('.booking-assistant').first();
    if (!$first.length) {
      return;
    }

    const $item = $('<div class="col-12 col-md-6 mb-3 booking-assistant-item"></div>');
    const $group = $('<div class="input-group"></div>');
    const $select = $first.clone();

    $select.val(value || '');
    $select.removeAttr('data-default-slot');
    $select.removeClass('is-invalid');

    $group.append($select);
    $group.append(
      '<button class="btn btn-outline-danger booking-remove-assistant" type="button" aria-label="Remove assistant">' +
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
    const customerIsCompany = getSelectedCustomerOption().data('type') === 'company';

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
    const customerIsCompany = getSelectedCustomerOption().data('type') === 'company';

    if (customerIsCompany && mode === 'pickup') {
      $('#mapModeDestination').prop('checked', true);
      updateMapStatus();
      return;
    }

    const lat = latlng.lat.toFixed(8);
    const lng = latlng.lng.toFixed(8);

    if (mode === 'pickup') {
      if (!pickupMarker) {
        pickupMarker = L.marker(latlng, { draggable: true, icon: pickupIcon }).addTo(map);
        pickupMarker.on('dragend', function () {
          const position = pickupMarker.getLatLng();
          setPickupCoordinates(position.lat, position.lng);
          fillAddressFromPin('pickup', position.lat, position.lng);
        });
      } else {
        pickupMarker.setLatLng(latlng);
      }

      setPickupCoordinates(lat, lng);
      fillAddressFromPin('pickup', lat, lng);
      $('#mapModeDestination').prop('checked', true);
      updateMapStatus();
      return;
    }

    if (!destinationMarker) {
      destinationMarker = L.marker(latlng, { draggable: true, icon: destinationIcon }).addTo(map);
      destinationMarker.on('dragend', function () {
        const position = destinationMarker.getLatLng();
        setDestinationCoordinates(position.lat, position.lng);
        fillAddressFromPin('destination', position.lat, position.lng);
      });
    } else {
      destinationMarker.setLatLng(latlng);
    }

    setDestinationCoordinates(lat, lng);
    fillAddressFromPin('destination', lat, lng);
  }

  function setPickupCoordinates(lat, lng) {
    const formattedLat = Number(lat).toFixed(8);
    const formattedLng = Number(lng).toFixed(8);

    $('#pickupLatitude').val(formattedLat);
    $('#pickupLongitude').val(formattedLng);
    $('#pickupCoordinateText').text(formattedLat + ', ' + formattedLng);
    $('#pickupLatitude, #pickupLongitude').removeClass('is-invalid');
  }

  function setDestinationCoordinates(lat, lng) {
    const formattedLat = Number(lat).toFixed(8);
    const formattedLng = Number(lng).toFixed(8);

    $('#destinationLatitude').val(formattedLat);
    $('#destinationLongitude').val(formattedLng);
    $('#destinationCoordinateText').text(formattedLat + ', ' + formattedLng);
    $('#destinationLatitude, #destinationLongitude').removeClass('is-invalid');
  }

  function fillAddressFromPin(type, lat, lng) {
    const label = type === 'pickup' ? 'Pickup' : 'Destination';
    const prefix = type === 'pickup' ? 'pickup' : 'destination';
    const previousStatus = $('#bookingMapStatus').text();
    const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' +
      encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&addressdetails=1';

    $('#bookingMapStatus').text('Looking up ' + label.toLowerCase() + ' address...');

    fetch(url, {
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Address lookup failed');
        }
        return response.json();
      })
      .then(function (data) {
        const address = data.address || {};
        const province = address.state || address.region || address.province || '';
        const city = address.city || address.town || address.municipality || address.county || '';
        const barangay = address.suburb || address.village || address.neighbourhood || address.quarter || address.city_district || '';
        const streetParts = [address.road, address.house_number].filter(Boolean);
        const street = streetParts.join(' ');

        setIfEmptyOrAutofilled(prefix + 'Province', province);
        setIfEmptyOrAutofilled(prefix + 'City', city);
        setIfEmptyOrAutofilled(prefix + 'Barangay', barangay);
        setIfEmptyOrAutofilled(prefix + 'Street', street);
        setIfEmptyOrAutofilled(prefix + 'Description', data.display_name || '');

        $('#' + prefix + 'Province, #' + prefix + 'City, #' + prefix + 'Barangay, #' + prefix + 'Street').removeClass('is-invalid');
        updateMapStatus();
      })
      .catch(function () {
        $('#bookingMapStatus').text(previousStatus || 'Address lookup failed. You can enter the address manually.');
      });
  }

  function setIfEmptyOrAutofilled(id, value) {
    if (!value) {
      return;
    }

    const $el = $('#' + id);
    const currentValue = String($el.val() || '').trim();

    if (currentValue === '' || $el.data('autofilled') === true) {
      $el.val(value);
      $el.data('autofilled', true);
    }
  }

  function validateInputs() {
    const allMissing = [];

    for (let step = 0; step < totalSteps - 1; step++) {
      allMissing.push(...validateStep(step));
    }

    return [...new Set(allMissing)];
  }

  function validateStep(step) {
    const missing = [];

    const check = (id, label) => {
      const $el = $('#' + id);
      const ok = String($el.val() || '').trim() !== '';

      if (!ok) {
        missing.push(label);
        $el.addClass('is-invalid');
      } else {
        $el.removeClass('is-invalid');
      }
    };

    if (step === 0) {
      check('bookingCustomer', 'Customer');
      check('bookingTruck', 'Truck');
      check('bookingDriver', 'Driver');
      check('bookingPickupDateTime', 'Pickup Date & Time');
      check('bookingPrice', 'Price');

      const assistantIDs = getAssistantIDs();
      if (assistantIDs.length < 2) {
        missing.push('At least 2 assistants');
        $('.booking-assistant').each(function () {
          if (!$(this).val()) {
            $(this).addClass('is-invalid');
          }
        });
      } else {
        $('.booking-assistant').removeClass('is-invalid');
      }

      if (new Set(assistantIDs).size !== assistantIDs.length) {
        missing.push('Assistants must be different employees');
        $('.booking-assistant').addClass('is-invalid');
      }

      const price = Number($('#bookingPrice').val());
      if ($('#bookingPrice').val() !== '' && (Number.isNaN(price) || price < 0)) {
        missing.push('Price must be a valid number');
        $('#bookingPrice').addClass('is-invalid');
      }
    }

    if (step === 1) {
      check('cargoType', 'Cargo Type');
      check('cargoQuantity', 'Cargo Quantity');

      const quantity = Number($('#cargoQuantity').val());
      if ($('#cargoQuantity').val() !== '' && (!Number.isInteger(quantity) || quantity < 1)) {
        missing.push('Cargo Quantity must be a whole number greater than 0');
        $('#cargoQuantity').addClass('is-invalid');
      }
    }

    if (step === 2) {
      check('pickupProvince', 'Pickup Province');
      check('pickupCity', 'Pickup City');
      check('pickupBarangay', 'Pickup Barangay');
      check('pickupStreet', 'Pickup Street');
      check('pickupLatitude', 'Pickup Map Pin');
      check('pickupLongitude', 'Pickup Map Pin');

      check('destinationProvince', 'Destination Province');
      check('destinationCity', 'Destination City');
      check('destinationBarangay', 'Destination Barangay');
      check('destinationStreet', 'Destination Street');
      check('destinationLatitude', 'Destination Map Pin');
      check('destinationLongitude', 'Destination Map Pin');
    }

    return [...new Set(missing)];
  }

  function updateReview() {
    const pickupAddress = formatAddress('pickup');
    const destinationAddress = formatAddress('destination');
    const assistantNames = $('.booking-assistant').map(function () {
      return $(this).find('option:selected').text().trim();
    }).get().filter(Boolean).join(', ');

    $('#reviewCustomer').text($('#bookingCustomer option:selected').text().trim() || '-');
    $('#reviewTripSchedule').text('Generated on save / ' + ($('#bookingPickupDateTime').val() || '-'));
    $('#reviewCrew').text(
      ($('#bookingTruck option:selected').text().trim() || '-') +
      ' / Driver: ' + ($('#bookingDriver option:selected').text().trim() || '-') +
      ' / Assistants: ' + (assistantNames || '-')
    );
    $('#reviewCargo').text(($('#cargoType').val() || '-') + ' x ' + ($('#cargoQuantity').val() || '-'));
    $('#reviewPrice').text($('#bookingPrice').val() || '-');
    $('#reviewPickup').text(pickupAddress + ' (' + ($('#pickupCoordinateText').text() || '-') + ')');
    $('#reviewDestination').text(destinationAddress + ' (' + ($('#destinationCoordinateText').text() || '-') + ')');
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
      missing.map(m => '<li>' + m + '</li>').join('') +
      '</ul>';

    Swal.fire({
      icon: 'warning',
      title: 'Missing Required Fields',
      html: '<p class="text-muted mb-2">Please review the following:</p>' + listHtml,
      confirmButtonText: 'OK',
      confirmButtonColor: '#696cff'
    });
  }

  function showConfirmModal() {
    Swal.fire({
      icon: 'question',
      title: 'Confirm Booking',
      html:
        '<p class="mb-2">Please review the details before submitting:</p>' +
        '<div class="text-start bg-light rounded p-3">' +
          '<div><strong>Customer:</strong> ' + ($('#bookingCustomer option:selected').text().trim() || '-') + '</div>' +
          '<div><strong>Trip ID:</strong> Generated on save</div>' +
          '<div><strong>Truck:</strong> ' + ($('#bookingTruck option:selected').text().trim() || '-') + '</div>' +
          '<div><strong>Driver:</strong> ' + ($('#bookingDriver option:selected').text().trim() || '-') + '</div>' +
          '<div><strong>Assistants:</strong> ' + ($('.booking-assistant').map(function () { return $(this).find('option:selected').text().trim(); }).get().filter(Boolean).join(', ') || '-') + '</div>' +
          '<div><strong>Cargo:</strong> ' + ($('#cargoType').val() || '-') + ' (' + ($('#cargoQuantity').val() || '-') + ')</div>' +
          '<div><strong>Pickup:</strong> ' + ($('#pickupCoordinateText').text() || '-') + '</div>' +
          '<div><strong>Destination:</strong> ' + ($('#destinationCoordinateText').text() || '-') + '</div>' +
        '</div>',
      showCancelButton: true,
      confirmButtonText: '<i class="ri-check-line"></i> Yes, Save',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#696cff',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        saveBooking();
      }
    });
  }

  function saveBooking() {
    const formData = new FormData();

    formData.append('customerID', $('#bookingCustomer').val());
    formData.append('truckID', $('#bookingTruck').val());
    formData.append('driverID', $('#bookingDriver').val());
    formData.append('assistantIDs', JSON.stringify(getAssistantIDs()));
    formData.append('pickupDateTime', $('#bookingPickupDateTime').val().replace('T', ' '));
    formData.append('price', $('#bookingPrice').val());
    formData.append('cargoType', $('#cargoType').val());
    formData.append('cargoQuantity', $('#cargoQuantity').val());
    formData.append('cargoCondition', $('#cargoCondition').val());
    formData.append('cargoDescription', $('#cargoDescription').val());
    formData.append('cargoSpecialHandling', $('#cargoSpecialHandling').val());

    formData.append('pickupProvince', $('#pickupProvince').val());
    formData.append('pickupCity', $('#pickupCity').val());
    formData.append('pickupBarangay', $('#pickupBarangay').val());
    formData.append('pickupStreet', $('#pickupStreet').val());
    formData.append('pickupDescription', $('#pickupDescription').val());
    formData.append('pickupLatitude', $('#pickupLatitude').val());
    formData.append('pickupLongitude', $('#pickupLongitude').val());

    formData.append('destinationProvince', $('#destinationProvince').val());
    formData.append('destinationCity', $('#destinationCity').val());
    formData.append('destinationBarangay', $('#destinationBarangay').val());
    formData.append('destinationStreet', $('#destinationStreet').val());
    formData.append('destinationDescription', $('#destinationDescription').val());
    formData.append('destinationLatitude', $('#destinationLatitude').val());
    formData.append('destinationLongitude', $('#destinationLongitude').val());

    $.ajax({
      url: 'ajax/booking_save_record.ajax.php',
      method: 'POST',
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      dataType: 'text',
      success: function (response) {
        const res = (response || '').trim();

        if (res === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Saved!',
            text: 'Booking saved successfully.',
            confirmButtonColor: '#696cff'
          }).then(() => {
            window.location = 'booking-reg';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to save booking. Please check that booking and location tables exist.',
            confirmButtonColor: '#696cff'
          });
        }
      },
      error: function () {
        Swal.fire({
          icon: 'error',
          title: 'Network Error',
          text: 'Something went wrong while saving.',
          confirmButtonColor: '#696cff'
        });
      }
    });
  }
});
