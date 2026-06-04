$(document).ready(function () {
  const truck = window.truckDetailData || {};

  $(document).on('click', '#truckUpdateReadingsBtn', openReadingsModal);
  $(document).on('click', '#truckAddFuelBtn, #truckAddFuelBtnSecondary', openFuelModal);

  function openReadingsModal() {
    Swal.fire({
      title: 'Update Current Readings',
      html:
        '<div class="truck-detail-form">' +
          field('truckReadingFuel', 'Current Fuel (L)', truck.fuel, 'number', '0.01') +
          field('truckReadingMileage', 'Current Mileage (km)', truck.mileage, 'number', '0.01') +
          '<p class="wide text-muted small mb-0">Use this when the actual fuel level or odometer reading changes outside a fuel entry.</p>' +
        '</div>',
      customClass: { popup: 'truck-detail-modal' },
      showCancelButton: true,
      confirmButtonText: 'Save Readings',
      confirmButtonColor: '#4e5dff',
      focusConfirm: false,
      preConfirm: function () {
        const fuel = numberValue('#truckReadingFuel');
        const mileage = numberValue('#truckReadingMileage');

        if (fuel < 0 || mileage < 0) {
          Swal.showValidationMessage('Fuel and mileage must be valid non-negative values.');
          return false;
        }

        return { action: 'readings', truckID: truck.truckID, fuel: fuel, mileage: mileage };
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        saveTruckDetail(result.value, 'Readings Updated');
      }
    });
  }

  function openFuelModal() {
    Swal.fire({
      title: 'Record Fuel Entry',
      html:
        '<div class="truck-detail-form">' +
          field('truckFuelLogDate', 'Date & Time', localDateTimeValue(), 'datetime-local') +
          field('truckFuelLiters', 'Liters Added', '', 'number', '0.01', 'Required') +
          field('truckFuelAfter', 'Fuel After Refuel (L)', truck.fuel, 'number', '0.01', 'Leave the calculated value or enter the actual reading') +
          field('truckFuelOdometer', 'Odometer (km)', truck.mileage, 'number', '0.01') +
          field('truckFuelAmount', 'Total Cost', '', 'number', '0.01', 'Optional') +
          field('truckFuelStation', 'Station', '', 'text', '', 'Optional') +
          field('truckFuelReference', 'Reference No.', '', 'text', '', 'Optional') +
          '<div class="wide"><label class="form-label" for="truckFuelNotes">Notes</label><textarea class="form-control" id="truckFuelNotes" rows="3" placeholder="Optional remarks"></textarea></div>' +
        '</div>',
      customClass: { popup: 'truck-detail-modal' },
      showCancelButton: true,
      confirmButtonText: 'Save Fuel Log',
      confirmButtonColor: '#4e5dff',
      focusConfirm: false,
      didOpen: function () {
        $('#truckFuelLiters').on('input', function () {
          const liters = numberValue('#truckFuelLiters');
          $('#truckFuelAfter').val((Number(truck.fuel || 0) + Math.max(liters, 0)).toFixed(2));
        });
      },
      preConfirm: function () {
        const litersAdded = numberValue('#truckFuelLiters');
        const fuelAfter = numberValue('#truckFuelAfter');
        const odometer = numberValue('#truckFuelOdometer');

        if (!$('#truckFuelLogDate').val() || litersAdded <= 0 || fuelAfter < 0 || odometer < 0) {
          Swal.showValidationMessage('Date, liters added, fuel after refuel, and odometer are required.');
          return false;
        }

        return {
          action: 'fuel',
          truckID: truck.truckID,
          logDate: $('#truckFuelLogDate').val().replace('T', ' '),
          litersAdded: litersAdded,
          fuelAfter: fuelAfter,
          odometer: odometer,
          amount: numberValue('#truckFuelAmount'),
          station: $('#truckFuelStation').val(),
          referenceNo: $('#truckFuelReference').val(),
          notes: $('#truckFuelNotes').val()
        };
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        saveTruckDetail(result.value, 'Fuel Log Saved');
      }
    });
  }

  function saveTruckDetail(payload, successTitle) {
    $.ajax({
      url: 'ajax/truck_detail_record.ajax.php',
      method: 'POST',
      data: payload,
      dataType: 'json',
      success: function (response) {
        if (response && response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: successTitle,
            text: response.message,
            timer: 1300,
            showConfirmButton: false
          }).then(function () {
            window.location.reload();
          });
          return;
        }

        showSaveError(response && response.message);
      },
      error: function () {
        showSaveError();
      }
    });
  }

  function showSaveError(message) {
    Swal.fire({
      icon: 'error',
      title: 'Unable to Save',
      text: message || 'Please check the values and try again.',
      confirmButtonColor: '#4e5dff'
    });
  }

  function field(id, label, value, type, step, help) {
    return '<div>' +
      '<label class="form-label" for="' + id + '">' + label + '</label>' +
      '<input type="' + (type || 'text') + '" class="form-control" id="' + id + '" value="' + escapeAttr(value) + '"' +
        (step ? ' step="' + step + '"' : '') + (type === 'number' ? ' min="0"' : '') + '>' +
      (help ? '<div class="form-text">' + help + '</div>' : '') +
    '</div>';
  }

  function numberValue(selector) {
    const value = Number($(selector).val());
    return Number.isFinite(value) ? value : -1;
  }

  function localDateTimeValue() {
    const date = new Date();
    const offset = date.getTimezoneOffset();
    return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 16);
  }

  function escapeAttr(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }
});
