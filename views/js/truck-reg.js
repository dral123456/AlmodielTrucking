$(document).ready(function () {

  $(document).on('click', '#truckBtnReset', function () {
    $('#truckPlateNumber, #truckBrand, #truckType, #truckCapacity, #truckFuel, #truckMileage, #truckDriver, #truckAssistant1, #truckAssistant2').val('');
    $('#truckCorDocument, #truckOtherDocument').val('');
    $('.is-invalid').removeClass('is-invalid');
    syncAssistantOptions();
  });

  $(document).on('change', '#truckAssistant1, #truckAssistant2', function () {
    syncAssistantOptions();
  });

  $(document).on('click', '#truckBtnRegister', function () {
    const missing = validateInputs();

    if (missing.length > 0) {
      showMissingModal(missing);
      return;
    }

    showConfirmModal();
  });

  function validateInputs() {
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

    check('truckPlateNumber', 'Plate Number');
    check('truckBrand', 'Brand');
    check('truckType', 'Type');
    check('truckCapacity', 'Capacity');
    check('truckFuel', 'Fuel');
    check('truckMileage', 'Mileage');
    check('truckCorDocument', 'COR Image');
    check('truckDriver', 'Driver');
    check('truckAssistant1', 'Assistant 1');
    check('truckAssistant2', 'Assistant 2');

    ['truckCapacity', 'truckFuel', 'truckMileage'].forEach((id) => {
      const $el = $('#' + id);
      const value = Number($el.val());

      if ($el.val() !== '' && (Number.isNaN(value) || value < 0)) {
        missing.push($el.closest('.mb-3').find('.form-label').text().replace('*', '').trim() + ' must be a valid number');
        $el.addClass('is-invalid');
      }
    });

    if ($('#truckAssistant1').val() && $('#truckAssistant1').val() === $('#truckAssistant2').val()) {
      missing.push('Assistant 1 and Assistant 2 must be different employees');
      $('#truckAssistant1, #truckAssistant2').addClass('is-invalid');
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const corFile = $('#truckCorDocument')[0].files[0];
    const otherFile = $('#truckOtherDocument')[0].files[0];

    if (corFile && !allowedTypes.includes(corFile.type)) {
      missing.push('COR Image must be JPG, PNG, or WEBP');
      $('#truckCorDocument').addClass('is-invalid');
    }

    if (otherFile && !allowedTypes.includes(otherFile.type)) {
      missing.push('OR / Other Truck Document must be JPG, PNG, or WEBP');
      $('#truckOtherDocument').addClass('is-invalid');
    }

    return missing;
  }

  function syncAssistantOptions() {
    const assistant1 = $('#truckAssistant1').val();
    const assistant2 = $('#truckAssistant2').val();

    $('#truckAssistant1 option, #truckAssistant2 option').prop('hidden', false).prop('disabled', false);

    if (assistant1) {
      $('#truckAssistant2 option[value="' + assistant1 + '"]').prop('hidden', true).prop('disabled', true);
    }

    if (assistant2) {
      $('#truckAssistant1 option[value="' + assistant2 + '"]').prop('hidden', true).prop('disabled', true);
    }
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
      title: 'Confirm Registration',
      html:
        '<p class="mb-2">Please review the details before submitting:</p>' +
        '<div class="text-start bg-light rounded p-3">' +
          '<div><strong>Plate Number:</strong> ' + ($('#truckPlateNumber').val() || '-') + '</div>' +
          '<div><strong>Brand / Type:</strong> ' + ($('#truckBrand').val() || '-') + ' / ' + ($('#truckType').val() || '-') + '</div>' +
          '<div><strong>COR Image:</strong> ' + (($('#truckCorDocument')[0].files[0] || {}).name || '-') + '</div>' +
          '<div><strong>Other Document:</strong> ' + (($('#truckOtherDocument')[0].files[0] || {}).name || '-') + '</div>' +
          '<div><strong>Driver:</strong> ' + ($('#truckDriver option:selected').text().trim() || '-') + '</div>' +
          '<div><strong>Assistant 1:</strong> ' + ($('#truckAssistant1 option:selected').text().trim() || '-') + '</div>' +
          '<div><strong>Assistant 2:</strong> ' + ($('#truckAssistant2 option:selected').text().trim() || '-') + '</div>' +
        '</div>',
      showCancelButton: true,
      confirmButtonText: '<i class="ri-check-line"></i> Yes, Register',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#696cff',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        saveTruck();
      }
    });
  }

  function saveTruck() {
    const formData = new FormData();

    formData.append('plateNumber', $('#truckPlateNumber').val());
    formData.append('brand', $('#truckBrand').val());
    formData.append('type', $('#truckType').val());
    formData.append('capacity', $('#truckCapacity').val());
    formData.append('fuel', $('#truckFuel').val());
    formData.append('mileage', $('#truckMileage').val());
    formData.append('driverID', $('#truckDriver').val());
    formData.append('assistant1ID', $('#truckAssistant1').val());
    formData.append('assistant2ID', $('#truckAssistant2').val());

    if ($('#truckCorDocument')[0].files[0]) {
      formData.append('corDocument', $('#truckCorDocument')[0].files[0]);
    }

    if ($('#truckOtherDocument')[0].files[0]) {
      formData.append('otherDocument', $('#truckOtherDocument')[0].files[0]);
    }

    $.ajax({
      url: 'ajax/truck_save_record.ajax.php',
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
            title: 'Registered!',
            text: 'Truck registered successfully.',
            confirmButtonColor: '#696cff'
          }).then(() => {
            window.location = 'truck-reg';
          });
        } else if (res === 'existing') {
          Swal.fire({
            icon: 'info',
            title: 'Already Exists',
            text: 'This truck is already registered.',
            confirmButtonColor: '#696cff'
          });
        } else if (res === 'invalid_file') {
          Swal.fire({
            icon: 'warning',
            title: 'Invalid File',
            text: 'Please upload JPG, PNG, or WEBP images only.',
            confirmButtonColor: '#696cff'
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to save truck.',
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

  syncAssistantOptions();

});
