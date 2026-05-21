$(document).ready(function () {
  let warehouseMap = null;
  let warehouseMarker = null;
  const customerTypeFromUrl = new URLSearchParams(window.location.search).get('type');
  const singleTypeMode = customerTypeFromUrl === 'company' || customerTypeFromUrl === 'individual';
  const companyOnlyMode = customerTypeFromUrl === 'company';

  function getCustomerType() {
    return $('#customerType').val();
  }

  function applyCustomerType() {
    const type = getCustomerType();
    const isCompany = type === 'company';

    $('.cust-type-tile').removeClass('active');
    $('.cust-type-tile[data-type="' + type + '"]').addClass('active');

    $('#custTypeBadge').html(
      isCompany
        ? '<i class="ri-building-2-line me-1"></i> Company'
        : '<i class="ri-user-line me-1"></i> Individual'
    );

    $('#individualForm').toggleClass('d-none', isCompany);
    $('#companyForm').toggleClass('d-none', !isCompany);
    $('#customerTypeChooser, #customerTypeDivider').toggleClass('d-none', singleTypeMode);
    $('.card-header h5').text(singleTypeMode ? (isCompany ? 'Company Registration' : 'Individual Registration') : 'Customer Registration');
    $('.card-header p').text(singleTypeMode
      ? (isCompany ? 'Register a new company customer and pin the warehouse location.' : 'Register a new individual customer.')
      : 'Register a new customer. Choose customer type to begin.');

    if (isCompany) {
      setTimeout(function () {
        initWarehouseMap();
        refreshWarehouseMap();
      }, 50);
    }
  }

  // Tile click → set hidden value + repaint
  $(document).on('click', '.cust-type-tile', function () {
    if (singleTypeMode) {
      return;
    }
    $('#customerType').val($(this).data('type'));
    applyCustomerType();
  });

  // Reset
  $(document).on('click', '#btnResetCustomer', function () {
    $('#individualForm input, #companyForm input').val('');
    $('#custPassword, #custPasswordConfirm').val('');
    $('#customerCoordinateText').text('Not pinned');
    $('.is-invalid').removeClass('is-invalid');
    $('#customerType').val(companyOnlyMode ? 'company' : 'individual');
    if (warehouseMarker && warehouseMap) {
      warehouseMap.removeLayer(warehouseMarker);
      warehouseMarker = null;
    }
    applyCustomerType();
  });

  // Show/hide password
  $(document).on('click', '#toggleCustPassword', function () {
    const $pwd = $('#custPassword');
    const isHidden = $pwd.attr('type') === 'password';
    $pwd.attr('type', isHidden ? 'text' : 'password');
    $(this).find('i').toggleClass('ri-eye-line', !isHidden).toggleClass('ri-eye-off-line', isHidden);
  });

  $(document).on('click', '#warehouseMapSearchBtn', function () {
    searchWarehouseAddress();
  });

  $(document).on('keydown', '#warehouseMapSearch', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      searchWarehouseAddress();
    }
  });

  // Register
  $(document).on('click', '#btnRegisterCustomer', function () {
    const missing = validateInputs();
    if (missing.length > 0) {
      showMissingModal(missing);
      return;
    }
    showConfirmModal();
  });

  function showConfirmModal() {
    const isCompany = getCustomerType() === 'company';
    const typeLabel = isCompany ? 'Company' : 'Individual';
    const name = isCompany
      ? $('#companyName').val()
      : ($('#firstName').val() + ' ' + $('#lastName').val());

    Swal.fire({
      icon: 'question',
      title: 'Confirm Registration',
      html:
        '<p class="mb-2">Please review the details before submitting:</p>' +
        '<div class="text-start bg-light rounded p-3">' +
          '<div><strong>Type:</strong> ' + typeLabel + '</div>' +
          '<div><strong>Name:</strong> ' + (name.trim() || '—') + '</div>' +
        '</div>',
      showCancelButton: true,
      confirmButtonText: '<i class="ri-check-line"></i> Yes, Register',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#696cff',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        saveCustomer();
      }
    });
  }

  // Init
  if (singleTypeMode) {
    $('#customerType').val(customerTypeFromUrl);
  }
  applyCustomerType();

  function initWarehouseMap() {
    if (warehouseMap || typeof L === 'undefined' || !document.getElementById('customerWarehouseMap')) {
      if (warehouseMap) {
        refreshWarehouseMap();
      }
      return;
    }

    warehouseMap = L.map('customerWarehouseMap').setView([10.6765, 122.9509], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(warehouseMap);

    warehouseMap.on('click', function (event) {
      setWarehousePin(event.latlng);
    });

    refreshWarehouseMap();
  }

  function refreshWarehouseMap() {
    if (!warehouseMap) {
      return;
    }

    setTimeout(function () {
      warehouseMap.invalidateSize();
    }, 150);

    setTimeout(function () {
      warehouseMap.invalidateSize();
    }, 350);
  }

  function setWarehousePin(latlng) {
    const lat = Number(latlng.lat).toFixed(8);
    const lng = Number(latlng.lng).toFixed(8);

    if (!warehouseMarker) {
      warehouseMarker = L.marker(latlng, { draggable: true }).addTo(warehouseMap);
      warehouseMarker.on('dragend', function () {
        const position = warehouseMarker.getLatLng();
        setWarehouseCoordinates(position.lat, position.lng);
        fillWarehouseAddress(position.lat, position.lng);
      });
    } else {
      warehouseMarker.setLatLng(latlng);
    }

    setWarehouseCoordinates(lat, lng);
    fillWarehouseAddress(lat, lng);
  }

  function setWarehouseCoordinates(lat, lng) {
    const formattedLat = Number(lat).toFixed(8);
    const formattedLng = Number(lng).toFixed(8);

    $('#warehouseLatitude').val(formattedLat);
    $('#warehouseLongitude').val(formattedLng);
    $('#customerCoordinateText').text(formattedLat + ', ' + formattedLng);
    $('#warehouseLatitude, #warehouseLongitude').removeClass('is-invalid');
  }

  function searchWarehouseAddress() {
    const query = String($('#warehouseMapSearch').val() || '').trim();

    if (!query) {
      $('#warehouseMapSearch').addClass('is-invalid');
      $('#customerMapStatus').text('Enter an address or place to search.');
      return;
    }

    initWarehouseMap();
    $('#warehouseMapSearch').removeClass('is-invalid');
    $('#warehouseMapSearchBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Searching');
    $('#customerMapStatus').text('Searching warehouse location...');

    const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&q=' +
      encodeURIComponent(query);

    fetch(url, {
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(function (response) {
        return response.ok ? response.json() : [];
      })
      .then(function (results) {
        if (!Array.isArray(results) || !results.length) {
          $('#customerMapStatus').text('No location found. Try a more specific address.');
          return;
        }

        const result = results[0];
        const latlng = {
          lat: Number(result.lat),
          lng: Number(result.lon)
        };

        if (!Number.isFinite(latlng.lat) || !Number.isFinite(latlng.lng)) {
          $('#customerMapStatus').text('Search result has no usable coordinates.');
          return;
        }

        setWarehousePin(latlng);
        warehouseMap.setView(latlng, 16);
        fillWarehouseAddress(latlng.lat, latlng.lng);
      })
      .catch(function () {
        $('#customerMapStatus').text('Search failed. Check your connection or enter the address manually.');
      })
      .finally(function () {
        $('#warehouseMapSearchBtn').prop('disabled', false).html('Search');
      });
  }

  function fillWarehouseAddress(lat, lng) {
    const previousStatus = $('#customerMapStatus').text();
    const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' +
      encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&addressdetails=1';

    $('#customerMapStatus').text('Looking up warehouse address...');

    fetch(url, {
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(function (response) {
        return response.ok ? response.json() : null;
      })
      .then(function (data) {
        if (!data || !data.address) {
          $('#customerMapStatus').text(previousStatus || 'Address lookup failed. You can enter the address manually.');
          return;
        }

        const address = data.address;
        $('#provinceCorp').val(address.state || address.region || address.province || '');
        $('#cityCorp').val(address.city || address.town || address.municipality || address.village || '');
        $('#barangayCorp').val(address.suburb || address.neighbourhood || address.quarter || address.barangay || '');
        $('#streetCorp').val(address.road || address.pedestrian || address.footway || '');
        $('#houseCorp').val(address.house_number || '');
        $('#customerMapStatus').text('Warehouse address filled from the map pin.');
      })
      .catch(function () {
        $('#customerMapStatus').text(previousStatus || 'Address lookup failed. You can enter the address manually.');
      });
  }


  // ===== Validation =====
  function validateInputs() {
    const isCompany = getCustomerType() === 'company';
    const missing = [];

    const check = (id, label) => {
      const $el = $('#' + id);
      const el = $el[0];
      if (!el) return;
      const isFile = el.type === 'file';
      const ok = isFile ? el.files.length > 0 : String($el.val() || '').trim() !== '';
      if (!ok) {
        missing.push(label);
        $el.addClass('is-invalid');
      } else {
        $el.removeClass('is-invalid');
      }
    };

    if (isCompany) {
      check('companyName',   'Company Name');
      check('contactPerson', 'Contact Person');
      check('emailCorp',     'Business Email');
      check('phoneCorp',     'Business Phone');
      check('provinceCorp',  'Province');
      check('cityCorp',      'City / Municipality');
      check('barangayCorp',  'Barangay');
      check('warehouseLatitude',  'Warehouse Map Pin');
      check('warehouseLongitude', 'Warehouse Map Pin');
      check('businessDoc',   'Business Registration Document');
    } else {
      check('firstName',     'First Name');
      check('lastName',      'Last Name');
      check('phoneIndiv',    'Phone Number');
      check('provinceIndiv', 'Province');
      check('cityIndiv',     'City / Municipality');
      check('barangayIndiv', 'Barangay');
    }

    // Shared password fields
    check('custPassword',        'Password');
    check('custPasswordConfirm', 'Confirm Password');

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
      missing.map(m => '<li>' + m + '</li>').join('') +
      '</ul>';

    Swal.fire({
      icon: 'warning',
      title: 'Missing Required Fields',
      html: '<p class="text-muted mb-2">Please fill in the following fields:</p>' + listHtml,
      confirmButtonText: 'OK',
      confirmButtonColor: '#696cff'
    });
  }


  // ===== Save =====
  function saveCustomer() {
    const isCompany = getCustomerType() === 'company';
    const formData = new FormData();
    formData.append('customerType', getCustomerType());
    formData.append('password',     $('#custPassword').val());

    if (isCompany) {
      formData.append('companyName',   $('#companyName').val());
      formData.append('contactPerson', $('#contactPerson').val());
      formData.append('email',         $('#emailCorp').val());
      formData.append('phoneNumber',   $('#phoneCorp').val());
      formData.append('province',      $('#provinceCorp').val());
      formData.append('city',          $('#cityCorp').val());
      formData.append('barangay',      $('#barangayCorp').val());
      formData.append('street',        $('#streetCorp').val());
      formData.append('houseNumber',   $('#houseCorp').val());
      formData.append('warehouseLatitude',  $('#warehouseLatitude').val());
      formData.append('warehouseLongitude', $('#warehouseLongitude').val());

      const bizDoc = $('#businessDoc')[0].files[0];
      const otherDocs = $('#otherDocs')[0].files;
      if (bizDoc) formData.append('businessDoc', bizDoc);
      for (let i = 0; i < otherDocs.length; i++) {
        formData.append('otherDocs[]', otherDocs[i]);
      }
    } else {
      formData.append('firstName',     $('#firstName').val());
      formData.append('lastName',      $('#lastName').val());
      formData.append('middleInitial', $('#middleInitial').val());
      formData.append('email',         $('#emailIndiv').val());
      formData.append('phoneNumber',   $('#phoneIndiv').val());
      formData.append('province',      $('#provinceIndiv').val());
      formData.append('city',          $('#cityIndiv').val());
      formData.append('barangay',      $('#barangayIndiv').val());
      formData.append('street',        $('#streetIndiv').val());
      formData.append('houseNumber',   $('#houseIndiv').val());
    }

    $.ajax({
      url: 'ajax/customer_save_record.ajax.php',
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
          Swal.fire({
            icon: 'info',
            title: 'Already Exists',
            text: 'This customer already exists.',
            confirmButtonColor: '#696cff'
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to save customer.',
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
