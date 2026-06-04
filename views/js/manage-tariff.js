$(document).ready(function () {
  if (!$('.tariff-page').length) {
    return;
  }

  filterTariffRows();

  $(document).on('input', '#tariffSearch', filterTariffRows);
  $(document).on('change', '#tariffCompanyFilter', filterTariffRows);

  $(document).on('submit', '#tariffFilterForm', function (event) {
    event.preventDefault();
    filterTariffRows();
  });

  $(document).on('click', '#tariffClearFilters', function () {
    $('#tariffCompanyFilter').val('');
    $('#tariffSearch').val('');
    filterTariffRows();
  });

  $(document).on('submit', '#tariffImportForm', function (event) {
    event.preventDefault();

    const form = this;
    const formData = new FormData(form);
    formData.append('action', 'import');
    formData.set('hasFuelSubsidy', $(form).find('[name="hasFuelSubsidy"]').is(':checked') ? '1' : '0');

    $.ajax({
      url: 'ajax/tariff_record.ajax.php',
      method: 'POST',
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      dataType: 'json',
      success: function (response) {
        if (response && response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'CSV Imported',
            text: response.saved + ' row(s) saved, ' + response.skipped + ' skipped.',
            confirmButtonColor: '#696cff'
          }).then(function () {
            window.location = 'manage-tariff?customerID=' + encodeURIComponent($(form).find('[name="customerID"]').val());
          });
          return;
        }

        Swal.fire({ icon: 'error', title: 'Import Failed', text: 'Please check the CSV format.' });
      },
      error: function () {
        Swal.fire({ icon: 'error', title: 'Import Failed', text: 'Please try again.' });
      }
    });
  });

  $(document).on('submit', '#tariffBulkFuelForm', function (event) {
    event.preventDefault();

    const form = this;
    const payload = {
      action: 'bulkFuelRange',
      customerID: $(form).find('[name="customerID"]').val(),
      truckType: $(form).find('[name="truckType"]').val(),
      fuelRangeStart: $(form).find('[name="fuelRangeStart"]').val(),
      fuelRangeEnd: $(form).find('[name="fuelRangeEnd"]').val(),
      hasFuelSubsidy: $(form).find('[name="hasFuelSubsidy"]').is(':checked') ? '1' : '0'
    };

    if (!payload.customerID || payload.fuelRangeStart === '' || payload.fuelRangeEnd === '') {
      Swal.fire({ icon: 'warning', title: 'Missing Details', text: 'Select company and enter the fuel range.' });
      return;
    }

    Swal.fire({
      icon: 'question',
      title: 'Apply Fuel Rule?',
      text: 'This will update matching tariff rows for the selected company.',
      showCancelButton: true,
      confirmButtonText: 'Apply',
      confirmButtonColor: '#696cff'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      $.post('ajax/tariff_record.ajax.php', payload, function (response) {
        if (response && response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Fuel Rule Updated',
            text: response.updated + ' tariff row(s) updated.',
            confirmButtonColor: '#696cff'
          }).then(function () {
            window.location = 'manage-tariff?customerID=' + encodeURIComponent(payload.customerID);
          });
          return;
        }

        Swal.fire({ icon: 'error', title: 'Update Failed', text: response && response.status ? 'Server response: ' + response.status : 'Please check the selected company and fuel range.' });
      }, 'json').fail(function () {
        Swal.fire({ icon: 'error', title: 'Update Failed', text: 'Please try again.' });
      });
    });
  });

  $(document).on('click', '#tariffAddBtn', function () {
    openTariffModal(null);
  });

  $(document).on('click', '.tariff-edit', function () {
    openTariffModal($(this).closest('tr'));
  });

  $(document).on('click', '.tariff-archive', function () {
    const $row = $(this).closest('tr');
    const tariffID = $row.data('tariff-id');

    Swal.fire({
      icon: 'warning',
      title: 'Archive Tariff?',
      text: 'This will deactivate the tariff row.',
      showCancelButton: true,
      confirmButtonText: 'Archive',
      confirmButtonColor: '#dc3545'
    }).then(function (result) {
      if (result.isConfirmed) {
        $.post('ajax/tariff_record.ajax.php', { action: 'archive', tariffID: tariffID }, handleSaveResponse, 'json');
      }
    });
  });

  function filterTariffRows() {
    const query = String($('#tariffSearch').val() || '').toLowerCase();
    const companyID = String($('#tariffCompanyFilter').val() || '');
    let visible = 0;

    $('#tariffTable tbody tr').each(function () {
      const $row = $(this);
      const matchesCompany = !companyID || String($row.data('customer-id')) === companyID;
      const matchesQuery = !query || $row.text().toLowerCase().includes(query);
      const match = matchesCompany && matchesQuery;
      $(this).toggle(match);
      if (match) {
        visible++;
      }
    });

    $('.tariff-empty').toggleClass('d-none', visible !== 0);
  }

  function openTariffModal($row) {
    const data = rowData($row);

    Swal.fire({
      title: data.tariffID ? 'Edit Tariff' : 'Add Tariff',
      html: tariffForm(data),
      width: 820,
      showCancelButton: true,
      confirmButtonText: 'Save Tariff',
      confirmButtonColor: '#696cff',
      focusConfirm: false,
      preConfirm: function () {
        const payload = { action: 'save' };
        $('.tariff-field').each(function () {
          payload[$(this).attr('name')] = $(this).val();
        });
        payload.hasFuelSubsidy = $('#tariffHasFuelSubsidy').is(':checked') ? 1 : 0;

        if (!payload.customerID || !payload.destination || !payload.truckType || Number(payload.baseRate) <= 0) {
          Swal.showValidationMessage('Company, destination, truck type, and base rate are required.');
          return false;
        }

        return payload;
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        $.post('ajax/tariff_record.ajax.php', result.value, handleSaveResponse, 'json');
      }
    });
  }

  function rowData($row) {
    if (!$row || !$row.length) {
      return {
        tariffID: '',
        customerID: $('#tariffCompanyFilter').val() || '',
        branch: 'BACOLOD',
        origin: 'BACOLOD',
        destination: '',
        distanceKm: '',
        truckType: '',
        baseRate: '',
        fuelRangeStart: '60',
        fuelRangeEnd: '65',
        hasFuelSubsidy: 1,
        fuelSubsidy: 0,
        status: 'active'
      };
    }

    return {
      tariffID: $row.data('tariff-id') || '',
      customerID: $row.data('customer-id') || '',
      branch: $row.data('branch') || 'BACOLOD',
      origin: $row.data('origin') || 'BACOLOD',
      destination: $row.data('destination') || '',
      distanceKm: $row.data('distance-km') || '',
      truckType: $row.data('truck-type') || '',
      baseRate: $row.data('base-rate') || '',
      fuelRangeStart: $row.data('fuel-range-start') || '',
      fuelRangeEnd: $row.data('fuel-range-end') || '',
      hasFuelSubsidy: Number($row.data('has-fuel-subsidy')) === 1 ? 1 : 0,
      fuelSubsidy: $row.data('fuel-subsidy') || 0,
      status: $row.data('status') || 'active'
    };
  }

  function tariffForm(data) {
    return '<div class="text-start salary-form-grid">' +
      hidden('tariffID', data.tariffID) +
      field('customerID', 'Company', companySelect(data.customerID)) +
      input('truckType', 'Truck Type', data.truckType, 'text', 'e.g. 6W') +
      input('branch', 'Branch', data.branch, 'text', 'BACOLOD') +
      input('origin', 'Origin', data.origin, 'text', 'BACOLOD') +
      input('destination', 'Destination', data.destination, 'text', 'CAUAYAN') +
      input('distanceKm', 'Distance KM', data.distanceKm, 'number', '0') +
      input('baseRate', 'Base / Current Rate', data.baseRate, 'number', '0.00') +
      input('fuelRangeStart', 'Base Fuel Start', data.fuelRangeStart, 'number', '60') +
      input('fuelRangeEnd', 'Base Fuel End', data.fuelRangeEnd, 'number', '65') +
      field('hasFuelSubsidy', 'Fuel Subsidy', '<label class="form-check tariff-check"><input class="form-check-input" type="checkbox" id="tariffHasFuelSubsidy" ' + (data.hasFuelSubsidy ? 'checked' : '') + '><span class="form-check-label">Apply fuel subsidy formula</span></label>') +
      field('status', 'Status', '<select class="form-select tariff-field" name="status"><option value="active"' + (data.status === 'active' ? ' selected' : '') + '>Active</option><option value="inactive"' + (data.status === 'inactive' ? ' selected' : '') + '>Inactive</option></select>') +
      hidden('fuelSubsidy', data.fuelSubsidy || 0) +
    '</div>';
  }

  function companySelect(value) {
    let html = '<select class="form-select tariff-field" name="customerID"><option value="">Select company</option>';
    (window.tariffCompanies || []).forEach(function (company) {
      html += '<option value="' + escapeAttr(company.id) + '"' + (String(company.id) === String(value) ? ' selected' : '') + '>' +
        escapeHtml(company.companyName || ('Company #' + company.id)) +
        '</option>';
    });
    return html + '</select>';
  }

  function field(name, label, control) {
    return '<div><label class="form-label">' + escapeHtml(label) + '</label>' + control + '</div>';
  }

  function input(name, label, value, type, placeholder) {
    return field(name, label, '<input type="' + type + '" class="form-control tariff-field" name="' + name + '" value="' + escapeAttr(value) + '" placeholder="' + escapeAttr(placeholder || '') + '" step="0.01">');
  }

  function hidden(name, value) {
    return '<input type="hidden" class="tariff-field" name="' + name + '" value="' + escapeAttr(value) + '">';
  }

  function handleSaveResponse(response) {
    if (response && response.status === 'success') {
      Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false })
        .then(function () { window.location.reload(); });
      return;
    }

    Swal.fire({ icon: 'error', title: 'Unable to Save', text: 'Please check the tariff details.' });
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
