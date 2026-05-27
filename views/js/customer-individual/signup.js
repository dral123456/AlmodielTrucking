$(document).ready(function () {

  // ===== STEPPER STATE =====
  let currentStep = 1;
  let regMap = null;
  let regMarker = null;

  function goToStep(step) {
    // Hide all steps
    $('#regStep1, #regStep2, #regStep3').hide();
    $('#regStep' + step).show();

    // Update dots
    for (let i = 1; i <= 3; i++) {
      const $dot = $('#stepDot' + i);
      $dot.removeClass('active done');
      if (i < step) $dot.addClass('done');
      else if (i === step) $dot.addClass('active');
    }

    // Update lines
    for (let i = 1; i <= 2; i++) {
      const $line = $('#stepLine' + i);
      $line.toggleClass('done', i < step);
    }

    currentStep = step;

    // Init map when arriving at step 2
    if (step === 2) {
      setTimeout(initRegMap, 100);
    }
  }

  function initRegMap() {
    if (regMap) {
      regMap.invalidateSize();
      return;
    }
    regMap = L.map('regMap').setView([10.6765, 122.9509], 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(regMap);

    regMap.on('click', async function (e) {
      const lat = e.latlng.lat;
      const lng = e.latlng.lng;
      placeMarker(lat, lng);
      await reverseGeocode(lat, lng);
    });
  }

  function placeMarker(lat, lng) {
    if (regMarker) regMap.removeLayer(regMarker);
    regMarker = L.marker([lat, lng]).addTo(regMap);
    regMap.panTo([lat, lng]);
    $('#lat').val(lat);
    $('#lng').val(lng);
  }

  async function reverseGeocode(lat, lng) {
    try {
      const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;
      const res = await fetch(url);
      const data = await res.json();
      const address = data.address || {};
      $('#provinceIndiv').val(address.state || '');
      $('#cityIndiv').val(address.city || address.town || address.municipality || '');
      $('#barangayIndiv').val(address.suburb || address.village || address.neighbourhood || address.quarter || address.hamlet || '');
      $('#streetIndiv').val(address.road || '');
    } catch (e) {
      console.error('Reverse geocode failed', e);
    }
  }

  // Map search
  $(document).on('click', '#mapSearchBtn', doMapSearch);
  $(document).on('keydown', '#mapSearchInput', function (e) {
    if (e.key === 'Enter') doMapSearch();
  });

  async function doMapSearch() {
    const query = $('#mapSearchInput').val().trim();
    if (!query) return;
    try {
      const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`;
      const res = await fetch(url);
      const data = await res.json();
      if (data && data.length > 0) {
        const lat = parseFloat(data[0].lat);
        const lng = parseFloat(data[0].lon);
        placeMarker(lat, lng);
        regMap.setView([lat, lng], 15);
        await reverseGeocode(lat, lng);
      } else {
        Swal.fire({ icon: 'info', title: 'Not found', text: 'No results for that location.', confirmButtonColor: '#696cff' });
      }
    } catch (e) {
      console.error('Search failed', e);
    }
  }

    // ===== LOCATION SUGGESTIONS =====
  let regSuggestTimer = null;

  $(document).on('input', '#mapSearchInput', function () {
    clearTimeout(regSuggestTimer);
    const query = $(this).val().trim();
    if (query.length < 2) {
      $('#regLocationSuggestions').hide().empty();
      return;
    }
    regSuggestTimer = setTimeout(function () {
      fetchRegSuggestions(query);
    }, 300);
  });

  function fetchRegSuggestions(query) {
    $.ajax({
      url: '/almodieltrucking/ajax/location_search.ajax.php',
      method: 'GET',
      data: { q: query },
      dataType: 'json',
      success: function (results) {
        const $box = $('#regLocationSuggestions');
        $box.empty();
        if (!results || results.length === 0) {
          $box.hide();
          return;
        }
        results.forEach(function (loc) {
          const $item = $('<div class="reg-suggestion-item"></div>').text(loc.label);
          $item.on('click', function () {
            // Fill search input
            $('#mapSearchInput').val(loc.label);
            $box.hide().empty();

            // Place marker on map
            if (loc.lat && loc.lng) {
              placeMarker(loc.lat, loc.lng);
              if (regMap) regMap.setView([loc.lat, loc.lng], 15);
            }

            // Fill address fields
            $('#provinceIndiv').val(loc.province  || '').removeClass('is-invalid');
            $('#cityIndiv').val(loc.city          || '').removeClass('is-invalid');
            $('#barangayIndiv').val(loc.barangay  || '').removeClass('is-invalid');
            $('#streetIndiv').val(loc.street      || '');
            $('#locationDescription').val(loc.description || '');
          });
          $box.append($item);
        });
        $box.show();
      }
    });
  }

  // Hide suggestions when clicking outside
  $(document).on('click', function (e) {
    if (!$(e.target).closest('#mapSearch').length) {
      $('#regLocationSuggestions').hide().empty();
    }
  });

  // ===== NAVIGATION =====
  $(document).on('input', '#phoneIndiv', function () {
    this.value = String(this.value || '').replace(/\D/g, '').slice(0, 11);
  });

  $(document).on('click', '#btnStep1Next', function () {
    const missing = validateStep1();
    if (missing.length > 0) { showMissingModal(missing); return; }
    goToStep(2);
  });

  $(document).on('click', '#btnStep2Prev', function () { goToStep(1); });

  $(document).on('click', '#btnStep2Next', function () {
    const missing = validateStep2();
    if (missing.length > 0) { showMissingModal(missing); return; }
    goToStep(3);
  });

  $(document).on('click', '#btnStep3Prev', function () { goToStep(2); });

  // ===== RESET =====
  $(document).on('click', '#btnResetCustomer', function () {
    $('#regStep1 input, #regStep2 input, #regStep3 input').not('[type=hidden]').val('');
    $('#locationDescription').val('');
    $('#lat, #lng').val('');
    $('.is-invalid').removeClass('is-invalid');
    if (regMarker) { regMap.removeLayer(regMarker); regMarker = null; }
    goToStep(1);
  });

  // ===== SHOW / HIDE PASSWORD =====
  $(document).on('click', '#toggleCustPassword', function () {
    const $pwd = $('#custPassword');
    const isHidden = $pwd.attr('type') === 'password';
    $pwd.attr('type', isHidden ? 'text' : 'password');
    $(this).find('i').toggleClass('ri-eye-line', !isHidden).toggleClass('ri-eye-off-line', isHidden);
  });

  $(document).on('click', '#toggleCustPasswordConfirm', function () {
    const $pwd = $('#custPasswordConfirm');
    const isHidden = $pwd.attr('type') === 'password';
    $pwd.attr('type', isHidden ? 'text' : 'password');
    $(this).find('i').toggleClass('ri-eye-line', !isHidden).toggleClass('ri-eye-off-line', isHidden);
  });

  // ===== REGISTER =====
  $(document).on('click', '#btnRegisterCustomer', function () {
    const missing = validateStep3();
    if (missing.length > 0) { showMissingModal(missing); return; }
    showConfirmModal();
  });

  function showConfirmModal() {
    const name = ($('#firstName').val() + ' ' + $('#lastName').val()).trim();
    Swal.fire({
      icon: 'question',
      title: 'Confirm Registration',
      html:
        '<p class="mb-2">Please review the details before submitting:</p>' +
        '<div class="text-start bg-light rounded p-3">' +
          '<div><strong>Type:</strong> Individual</div>' +
          '<div><strong>Name:</strong> ' + (name || '—') + '</div>' +
        '</div>',
      showCancelButton: true,
      confirmButtonText: '<i class="ri-check-line"></i> Yes, Register',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#696cff',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) saveLocation();
    });
  }

  // ===== VALIDATION =====
  function check(id, label, missing) {
    const $el = $('#' + id);
    if (!$el.length) return;
    const ok = String($el.val() || '').trim() !== '';
    if (!ok) { missing.push(label); $el.addClass('is-invalid'); }
    else $el.removeClass('is-invalid');
  }

  function validateStep1() {
    const missing = [];
    check('firstName',  'First Name',    missing);
    check('lastName',   'Last Name',     missing);
    check('phoneIndiv', 'Phone Number',  missing);
    const phone = String($('#phoneIndiv').val() || '');

    if (phone && !/^09\d{9}$/.test(phone)) {
      missing.push('Phone Number (must be 11 digits and start with 09)');
      $('#phoneIndiv').addClass('is-invalid');
    }

    return missing;
  }

  function validateStep2() {
    const missing = [];
    check('provinceIndiv', 'Province',            missing);
    check('cityIndiv',     'City / Municipality', missing);
    check('barangayIndiv', 'Barangay',            missing);
    if (!String($('#lat').val() || '').trim() || !String($('#lng').val() || '').trim()) {
      missing.push('Map Pin');
    }
    return missing;
  }

  function validateStep3() {
    const missing = [];
    check('custPassword',        'Password',         missing);
    check('custPasswordConfirm', 'Confirm Password', missing);
    const pwd     = String($('#custPassword').val() || '');
    const pwdConf = String($('#custPasswordConfirm').val() || '');
    if (pwd && pwd.length < 6) {
      missing.push('Password (must be at least 6 characters)');
      $('#custPassword').addClass('is-invalid');
    }
    if (pwd && pwdConf && pwd !== pwdConf) {
      missing.push('Password and Confirm Password must match');
      $('#custPassword, #custPasswordConfirm').addClass('is-invalid');
    }
    return missing;
  }

  function showMissingModal(missing) {
    const listHtml = '<ul class="text-start mb-0 ps-3">' +
      missing.map(m => '<li>' + m + '</li>').join('') + '</ul>';
    Swal.fire({
      icon: 'warning',
      title: 'Missing Required Fields',
      html: '<p class="text-muted mb-2">Please fill in the following fields:</p>' + listHtml,
      confirmButtonText: 'OK',
      confirmButtonColor: '#696cff'
    });
  }

  // ===== STEP 1: SAVE LOCATION =====
  function saveLocation() {
    const house = $('#houseIndiv').val().trim();
    const desc  = $('#locationDescription').val().trim();

    // Combine house number into description
    let combinedDesc = desc;
    if (house) {
      combinedDesc = house + (desc ? ' — ' + desc : '');
    }

    const locationData = new FormData();
    locationData.append('province',    $('#provinceIndiv').val());
    locationData.append('city',        $('#cityIndiv').val());
    locationData.append('barangay',    $('#barangayIndiv').val());
    locationData.append('street',      $('#streetIndiv').val());
    locationData.append('houseNumber', house);
    locationData.append('description', combinedDesc);   // ← merged
    locationData.append('latitude',    $('#lat').val()); // ← match your PHP field names
    locationData.append('longitude',   $('#lng').val());

    $.ajax({
      url: '/almodieltrucking/ajax/location_save_record.ajax.php',
      method: 'POST',
      data: locationData,
      cache: false,
      contentType: false,
      processData: false,
      dataType: 'json',
      success: function (res) {
        if (res.status === 'success' && res.locationID) {
          saveCustomer(res.locationID);
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save location. Please try again.', confirmButtonColor: '#696cff' });
        }
      },
      error: function () {
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Something went wrong while saving location.', confirmButtonColor: '#696cff' });
      }
    });
  }

  // ===== STEP 2: SAVE CUSTOMER (with locationID) =====
  function saveCustomer(locationID) {
    const formData = new FormData();
    formData.append('customerType',  'individual');
    formData.append('password',      $('#custPassword').val());
    formData.append('firstName',     $('#firstName').val());
    formData.append('lastName',      $('#lastName').val());
    formData.append('middleInitial', $('#middleInitial').val());
    formData.append('email',         $('#emailIndiv').val());
    formData.append('phoneNumber',   $('#phoneIndiv').val());
    formData.append('locationID',    locationID);

    $.ajax({
      url: '/almodieltrucking/ajax/customer_save_record.ajax.php',
      method: 'POST',
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      dataType: 'text',
      success: function (response) {
        const res = response.trim();
        if (res === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Registered!',
            text: 'Customer registered successfully.',
            confirmButtonColor: '#696cff'
          }).then(() => location.reload());
        } else if (res === 'existing') {
          Swal.fire({ icon: 'info',  title: 'Already Exists', text: 'This customer already exists.', confirmButtonColor: '#696cff' });
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: res || 'Failed to save customer.', confirmButtonColor: '#696cff' });
        }
      },
      error: function () {
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Something went wrong while saving.', confirmButtonColor: '#696cff' });
      }
    });
  }

  // Init at step 1
  goToStep(1);

});
