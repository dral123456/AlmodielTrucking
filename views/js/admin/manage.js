$(document).ready(function () {
  initCompanyManageMap();

  $(document).on('input', '.manage-search', function () {
    filterManageTable($(this).data('target'));
  });

  $(document).on('change', '.manage-status-filter', function () {
    filterManageTable($(this).data('target'));
  });

  $(document).on('click', '.manage-archive', function () {
    const $row = $(this).closest('tr');
    const label = $row.find('td:first strong').text().trim() || 'this record';

    Swal.fire({
      icon: 'warning',
      title: 'Archive Record?',
      text: 'This will set ' + label + ' as inactive.',
      showCancelButton: true,
      confirmButtonText: 'Archive',
      confirmButtonColor: '#dc3545'
    }).then(function (result) {
      if (result.isConfirmed) {
        saveManageRecord($row, { action: 'archive' });
      }
    });
  });

  $(document).on('click', '.manage-edit', function () {
    const $row = $(this).closest('tr');
    const entity = String($row.data('entity') || '');
    const html = buildEditForm($row, entity);

    Swal.fire({
      title: entity === 'company' ? 'Edit Company & Warehouse Pin' : 'Edit Record',
      html: html,
      width: entity === 'company' ? '98vw' : 720,
      customClass: entity === 'company' ? {
        popup: 'manage-company-edit-modal'
      } : {},
      showCancelButton: true,
      confirmButtonText: 'Save Changes',
      confirmButtonColor: '#696cff',
      focusConfirm: false,
      didOpen: function () {
        if (entity === 'company') {
          initCompanyEditMap($row);
        }
      },
      preConfirm: function () {
        const data = { action: 'edit' };
        $('.manage-edit-field').each(function () {
          data[$(this).attr('name')] = $(this).val();
        });
        return data;
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        saveManageRecord($row, result.value);
      }
    });
  });

  $(document).on('click', '.manage-crew', function () {
    const $row = $(this).closest('tr');

    Swal.fire({
      title: 'Reassign Truck Crew',
      html: buildCrewForm($row),
      width: 720,
      showCancelButton: true,
      confirmButtonText: 'Save Crew',
      confirmButtonColor: '#696cff',
      didOpen: function () {
        syncCrewAssistantOptions();
      },
      preConfirm: function () {
        const assistantIDs = $('.manage-crew-assistant').map(function () {
          return String($(this).val() || '').trim();
        }).get().filter(Boolean);

        if (!$('#manageCrewDriver').val() || assistantIDs.length < 2) {
          Swal.showValidationMessage('Select one driver and at least two assistants.');
          return false;
        }

        return {
          action: 'crew',
          driverID: $('#manageCrewDriver').val(),
          assistantIDs: JSON.stringify(assistantIDs)
        };
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        saveManageRecord($row, result.value);
      }
    });
  });

  $(document).on('click', '#manageAddCrewAssistant', function () {
    $('#manageCrewAssistantList').append(assistantSelect(''));
    syncCrewAssistantOptions();
  });

  $(document).on('click', '.manage-remove-crew-assistant', function () {
    $(this).closest('.manage-crew-assistant-item').remove();
    syncCrewAssistantOptions();
  });

  $(document).on('change', '#manageCrewDriver, .manage-crew-assistant', syncCrewAssistantOptions);

  function filterManageTable(target) {
    const $table = $(target);
    const query = String($('.manage-search[data-target="' + target + '"]').val() || '').toLowerCase();
    const status = String($('.manage-status-filter[data-target="' + target + '"]').val() || 'all');
    let visibleCount = 0;

    $table.find('tbody tr').each(function () {
      const rowText = $(this).text().toLowerCase();
      const rowStatus = String($(this).data('status') || '');
      const visible = (!query || rowText.includes(query)) && (status === 'all' || rowStatus === status);

      $(this).toggle(visible);
      if (visible) {
        visibleCount++;
      }
    });

    $table.closest('.card-body').find('.manage-empty').toggleClass('d-none', visibleCount !== 0);
  }

  function initCompanyManageMap() {
    const companies = Array.isArray(window.companyWarehouseMapData) ? window.companyWarehouseMapData : [];

    if (typeof L === 'undefined' || !document.getElementById('companyManageMap')) {
      return;
    }

    const map = L.map('companyManageMap').setView([10.6765, 122.9509], 11);
    const bounds = [];

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    companies.forEach(function (company) {
      const latlng = [company.latitude, company.longitude];
      const popup = '<strong>' + escapeHtml(company.name || 'Company') + '</strong><br>' +
        escapeHtml(company.address || '-') + '<br>' +
        '<span class="text-muted">' + escapeHtml(company.contactPerson || '') + '</span>';

      L.marker(latlng).addTo(map).bindPopup(popup);
      bounds.push(latlng);
    });

    if (bounds.length === 1) {
      map.setView(bounds[0], 14);
    } else if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [24, 24] });
    }

    setTimeout(function () {
      map.invalidateSize();
    }, 200);
  }

  function buildEditForm($row, entity) {
    const status = $row.data('status') || 'active';

    if (entity === 'company') {
      return companyEditMapHtml($row) + formGrid([
        input('companyName',   'Company Name',   $row.data('company-name')),
        input('contactPerson', 'Contact Person', $row.data('contact-person')),
        input('email',         'Email',          $row.data('email')),
        input('phoneNumber',   'Phone Number',   $row.data('phone-number')),
        input('province',      'Province',       $row.data('province')),
        input('city',          'City',           $row.data('city')),
        input('barangay',      'Barangay',       $row.data('barangay')),
        input('street',        'Street',         $row.data('street')),
        textarea('description', 'Description / Landmark', $row.data('description')),
        hiddenInput('latitude',    $row.data('latitude')),
        hiddenInput('longitude',   $row.data('longitude')),
        hiddenInput('locationId',  $row.data('location-id')),
        statusSelect(status)
      ]);
    }

    if (entity === 'employee') {
      return formGrid([
        input('firstName',   'First Name',   $row.data('first-name')),
        input('lastName',    'Last Name',    $row.data('last-name')),
        input('phoneNumber', 'Phone Number', $row.data('phone-number')),
        input('email',       'Email',        $row.data('email')),
        select('empType', 'Role', $row.data('emp-type'), { driver: 'Driver', assistant: 'Assistant' }),
        statusSelect(status)
      ]);
    }

    return formGrid([
      input('plateNumber', 'Plate Number', $row.data('plate-number')),
      input('brand',       'Brand',        $row.data('brand')),
      input('type',        'Type',         $row.data('type')),
      input('capacity',    'Capacity',     $row.data('capacity'), 'number'),
      input('fuel',        'Fuel',         $row.data('fuel'),     'number'),
      input('mileage',     'Mileage',      $row.data('mileage'),  'number'),
      statusSelect(status)
    ]);
  }

  function buildCrewForm($row) {
    const currentDriverID = String($row.data('driver-id') || '');
    const assistantIDs = String($row.data('assistant-ids') || '').split(',').filter(Boolean);
    while (assistantIDs.length < 2) {
      assistantIDs.push('');
    }
    const assistantFields = assistantIDs.map(assistantSelect).join('');

    return '<div class="text-start">' +
      '<label class="form-label">Driver</label>' +
      selectHtml('manageCrewDriver', 'form-select mb-3', window.manageDrivers || [], currentDriverID, false) +
      '<label class="form-label">Assistants</label>' +
      '<div id="manageCrewAssistantList" class="row g-2">' + assistantFields + '</div>' +
      '<button type="button" class="btn btn-sm btn-light mt-3" id="manageAddCrewAssistant">' +
      '<i class="ri-add-line me-1"></i> Add Assistant' +
      '</button>' +
      '</div>';
  }

  function assistantSelect(value) {
    return '<div class="col-12 col-md-6 manage-crew-assistant-item">' +
      '<div class="input-group">' +
      selectHtml('', 'form-select manage-crew-assistant', window.manageAssistants || [], String(value || ''), true) +
      '<button type="button" class="btn btn-outline-danger manage-remove-crew-assistant"><i class="ri-close-line"></i></button>' +
      '</div>' +
      '</div>';
  }

  function selectHtml(id, className, employees, value, assistant) {
    let html = '<select ' + (id ? 'id="' + id + '" ' : '') + 'class="' + className + '">';
    html += '<option value="">Select ' + (assistant ? 'assistant' : 'driver') + '</option>';
    employees.forEach(function (employee) {
      const employeeID = String(employee.id);
      const name = [employee.empFName, employee.empLName].filter(Boolean).join(' ');
      html += '<option value="' + escapeAttr(employeeID) + '"' + (employeeID === String(value) ? ' selected' : '') + '>' +
        escapeHtml(name) +
        '</option>';
    });
    return html + '</select>';
  }

  function syncCrewAssistantOptions() {
    const selectedDriver = String($('#manageCrewDriver').val() || '');
    const selectedAssistants = $('.manage-crew-assistant').map(function () {
      return String($(this).val() || '');
    }).get().filter(Boolean);

    $('.manage-crew-assistant option').prop('hidden', false).prop('disabled', false);

    $('.manage-crew-assistant').each(function () {
      const currentValue = String($(this).val() || '');

      if (selectedDriver) {
        $(this).find('option[value="' + selectedDriver + '"]').prop('hidden', true).prop('disabled', true);
      }

      selectedAssistants.forEach(function (assistantID) {
        if (assistantID !== currentValue) {
          $(this).find('option[value="' + assistantID + '"]').prop('hidden', true).prop('disabled', true);
        }
      }, this);
    });
  }

  function companyEditMapHtml($row) {
    const lat = String($row.data('latitude') || '');
    const lng = String($row.data('longitude') || '');
    const coordinateText = lat && lng ? lat + ', ' + lng : 'Not pinned';

    return '<div class="text-start mb-3">' +
      '<div class="manage-edit-map-panel border rounded p-3 bg-light-subtle">' +
      '<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">' +
      '<label class="form-label mb-0"><i class="ri-map-pin-2-line me-1"></i> Warehouse Pin</label>' +
      '<span class="badge bg-primary-subtle text-primary" id="manageCompanyCoordinateText">' + escapeHtml(coordinateText) + '</span>' +
      '</div>' +
      '<p class="text-muted small mb-2" id="manageCompanyMapStatus">Click anywhere on this map to set the warehouse pin. You can drag the marker after placing it.</p>' +
      '<div class="manage-company-map-search input-group mb-3">' +
      '<span class="input-group-text"><i class="ri-search-line"></i></span>' +
      '<input type="text" class="form-control" id="manageCompanyMapSearch" placeholder="Search warehouse address or place">' +
      '<button type="button" class="btn btn-primary" id="manageCompanyMapSearchBtn">Search</button>' +
      '</div>' +
      '<div id="manageCompanyEditMap"></div>' +
      '</div>' +
      '</div>';
  }

  function initCompanyEditMap($row) {
    if (typeof L === 'undefined' || !document.getElementById('manageCompanyEditMap')) {
      return;
    }

    const rawLat = String($row.data('latitude') || '').trim();
    const rawLng = String($row.data('longitude') || '').trim();
    const lat = Number(rawLat);
    const lng = Number(rawLng);
    const hasPin = rawLat !== '' && rawLng !== '' && Number.isFinite(lat) && Number.isFinite(lng);
    const start = hasPin ? [lat, lng] : [10.6765, 122.9509];
    const map = L.map('manageCompanyEditMap').setView(start, hasPin ? 15 : 12);
    let marker = null;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    function setSearchLoading(loading) {
      $('#manageCompanyMapSearchBtn')
        .prop('disabled', loading)
        .html(loading ? '<span class="spinner-border spinner-border-sm me-1"></span> Searching' : 'Search');
    }

    function setPin(latlng) {
      const nextLat = Number(latlng.lat).toFixed(8);
      const nextLng = Number(latlng.lng).toFixed(8);

      if (!marker) {
        marker = L.marker(latlng, { draggable: true }).addTo(map);
        marker.on('dragend', function () {
          setPin(marker.getLatLng());
        });
      } else {
        marker.setLatLng(latlng);
      }

      $('input[name="latitude"]').val(nextLat);
      $('input[name="longitude"]').val(nextLng);
      $('#manageCompanyCoordinateText').text(nextLat + ', ' + nextLng);
      $('#manageCompanyMapStatus').text('Warehouse pin updated. Save changes to apply it.');
    }

    function fillAddressFromResult(address) {
      if (!address) return;
      $('input[name="province"]').val(address.state || address.region || address.province || '');
      $('input[name="city"]').val(address.city || address.town || address.municipality || address.village || address.county || '');
      $('input[name="barangay"]').val(address.suburb || address.neighbourhood || address.quarter || address.barangay || '');
      $('input[name="street"]').val(address.road || address.pedestrian || address.footway || '');
    }

    function searchWarehouseAddress() {
      const query = String($('#manageCompanyMapSearch').val() || '').trim();

      if (!query) {
        $('#manageCompanyMapSearch').addClass('is-invalid');
        $('#manageCompanyMapStatus').text('Enter an address or place to search.');
        return;
      }

      $('#manageCompanyMapSearch').removeClass('is-invalid');
      $('#manageCompanyMapStatus').text('Searching warehouse location...');
      setSearchLoading(true);

      const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&q=' +
        encodeURIComponent(query);

      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function (response) {
          return response.ok ? response.json() : [];
        })
        .then(function (results) {
          if (!Array.isArray(results) || !results.length) {
            $('#manageCompanyMapStatus').text('No location found. Try a more specific address.');
            return;
          }

          const result = results[0];
          const latlng = { lat: Number(result.lat), lng: Number(result.lon) };

          if (!Number.isFinite(latlng.lat) || !Number.isFinite(latlng.lng)) {
            $('#manageCompanyMapStatus').text('Search result has no usable coordinates.');
            return;
          }

          setPin(latlng);
          fillAddressFromResult(result.address);
          map.setView(latlng, 16);
          $('#manageCompanyMapStatus').text('Warehouse pin set from search. Save changes to apply it.');
        })
        .catch(function () {
          $('#manageCompanyMapStatus').text('Search failed. Check your connection or click the map manually.');
        })
        .finally(function () {
          setSearchLoading(false);
        });
    }

    map.on('click', function (event) {
      setPin(event.latlng);
    });

    $('#manageCompanyMapSearchBtn').on('click', searchWarehouseAddress);
    $('#manageCompanyMapSearch').on('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        searchWarehouseAddress();
      }
    });

    if (hasPin) {
      setPin({ lat: lat, lng: lng });
    }

    setTimeout(function () { map.invalidateSize(); }, 150);
    setTimeout(function () { map.invalidateSize(); }, 450);
    setTimeout(function () { map.invalidateSize(); }, 800);
  }

  function saveManageRecord($row, payload) {
    payload.entity = $row.data('entity');
    payload.id     = $row.data('id');

    $.ajax({
      url: 'ajax/manage_record.ajax.php',
      method: 'POST',
      data: payload,
      dataType: 'text',
      success: function (response) {
        if (String(response).trim() === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            text: 'Record updated successfully.',
            confirmButtonColor: '#696cff'
          }).then(function () {
            location.reload();
          });
        } else {
          showManageError();
        }
      },
      error: showManageError
    });
  }

  function showManageError() {
    Swal.fire({
      icon: 'error',
      title: 'Unable to Save',
      text: 'Please try again.',
      confirmButtonColor: '#696cff'
    });
  }

  function formGrid(fields) {
    return '<div class="row g-3 text-start">' + fields.join('') + '</div>';
  }

  function input(name, label, value, type) {
    return '<div class="col-12 col-md-6">' +
      '<label class="form-label">' + label + '</label>' +
      '<input type="' + (type || 'text') + '" class="form-control manage-edit-field" name="' + name + '" value="' + escapeAttr(value) + '">' +
      '</div>';
  }

  function textarea(name, label, value) {
    return '<div class="col-12">' +
      '<label class="form-label">' + label + '</label>' +
      '<textarea class="form-control manage-edit-field" name="' + name + '" rows="2" style="resize:none;">' +
      escapeHtml(value) +
      '</textarea>' +
      '</div>';
  }

  function hiddenInput(name, value) {
    return '<input type="hidden" class="manage-edit-field" name="' + name + '" value="' + escapeAttr(value) + '">';
  }

  function select(name, label, value, options) {
    let html = '<div class="col-12 col-md-6"><label class="form-label">' + label + '</label><select class="form-select manage-edit-field" name="' + name + '">';
    Object.keys(options).forEach(function (key) {
      html += '<option value="' + key + '"' + (String(value) === key ? ' selected' : '') + '>' + options[key] + '</option>';
    });
    return html + '</select></div>';
  }

  function statusSelect(value) {
    return select('status', 'Status', value, { active: 'Active', inactive: 'Inactive' });
  }

  function escapeAttr(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
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