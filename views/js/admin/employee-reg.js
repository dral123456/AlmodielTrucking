$(document).ready(function () {

  // Date picker (guarded so a failure here doesn't kill the rest of the script)
  try {
    if (typeof AirDatepicker !== 'undefined' && document.getElementById('empBirthDate')) {
      new AirDatepicker('#empBirthDate', {
        dateFormat: 'yyyy-MM-dd',
        autoClose: true,
        isMobile: true,
        fixedHeight: true,
        locale: {
          days: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
          daysShort: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
          daysMin: ['Su','Mo','Tu','We','Th','Fr','Sa'],
          months: ['January','February','March','April','May','June','July','August','September','October','November','December'],
          monthsShort: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
          today: 'Today',
          clear: 'Clear',
          dateFormat: 'yyyy-MM-dd',
          timeFormat: 'HH:mm',
          firstDay: 0
        }
      });
    }
  } catch (e) {
    console.warn('AirDatepicker init failed:', e);
  }

  function getEmpType() {
    return $('#empType').val();
  }

  function applyEmpType() {
    const type = getEmpType();
    $('.emp-type-tile').removeClass('active');
    $('.emp-type-tile[data-type="' + type + '"]').addClass('active');

    const labels = {
      driver: '<i class="ri-steering-2-line me-1"></i> Driver',
      assistant: '<i class="ri-user-2-line me-1"></i> Assistant',
      admin: '<i class="ri-shield-user-line me-1"></i> Admin'
    };

    $('#empTypeBadge').html(labels[type] || labels.driver);
    $('#licenseInfoSection').toggleClass('d-none', type !== 'driver');
    if (type !== 'driver') {
      $('#licenseNumber, #expire, #licenseImage').val('').removeClass('is-invalid');
    }
  }

  // Tile click
  $(document).on('click', '.emp-type-tile', function () {
    $('#empType').val($(this).data('type'));
    applyEmpType();
  });

  // Reset
  $(document).on('click', '#empBtnReset', function () {
    $('#empFName, #empLName, #empMI, #empSuffix, #empBirthDate, #empPhoneNumber, #empEmail, #empPassword, #empPasswordConfirm').val('');
    $('.is-invalid').removeClass('is-invalid');
    $('#empType').val('driver');
    applyEmpType();
  });

  // Show/hide password
  $(document).on('click', '#togglePassword', function () {
    const $pwd = $('#empPassword');
    const isHidden = $pwd.attr('type') === 'password';
    $pwd.attr('type', isHidden ? 'text' : 'password');
    $(this).find('i').toggleClass('ri-eye-line', !isHidden).toggleClass('ri-eye-off-line', isHidden);
  });

  // Register
  $(document).on('click', '#empBtnRegister', function () {
  const missing = validateInputs();
  if (missing.length > 0) {
    showMissingModal(missing);
    return;
  }

  // Create FormData for AJAX
  const formData = new FormData();
  formData.append('empType', $('#empType').val());
  formData.append('empFName', $('#empFName').val());
  formData.append('empLName', $('#empLName').val());
  formData.append('empMI', $('#empMI').val());
  formData.append('empSuffix', $('#empSuffix').val());
  formData.append('empBirthDate', $('#empBirthDate').val());
  formData.append('empPhoneNumber', $('#empPhoneNumber').val());
  formData.append('empEmail', $('#empEmail').val());
  formData.append('empPassword', $('#empPassword').val());

  // Only add license info if type is driver
  if ($('#empType').val() === 'driver') {
    formData.append('licenseNumber', $('#licenseNumber').val());
    formData.append('expire', $('#expire').val());
    const fileInput = $('#licenseImage')[0];
    if (fileInput.files.length > 0) {
      formData.append('licenseImage', fileInput.files[0]);
    }
  }

  showConfirmModal(formData);
});

  // Init
  applyEmpType();


  // ===== Validation =====
  function validateInputs() {
  const missing = [];

  const check = (id, label) => {
    const $el = $('#' + id);
    const el = $el[0];
    if (!el) return;
    const ok = String($el.val() || '').trim() !== '';
    if (!ok) {
      missing.push(label);
      $el.addClass('is-invalid');
    } else {
      $el.removeClass('is-invalid');
    }
  };

  // Always required fields
  check('empFName',           'First Name');
  check('empLName',           'Last Name');
  check('empBirthDate',       'Birth Date');
  check('empPhoneNumber',     'Phone Number');
  check('empPassword',        'Password');
  check('empPasswordConfirm', 'Confirm Password');

  // Password rules
  const pwd     = String($('#empPassword').val() || '');
  const pwdConf = String($('#empPasswordConfirm').val() || '');
  if (pwd && pwd.length < 6) {
    missing.push('Password (must be at least 6 characters)');
    $('#empPassword').addClass('is-invalid');
  }
  if (pwd && pwdConf && pwd !== pwdConf) {
    missing.push('Password and Confirm Password must match');
    $('#empPassword, #empPasswordConfirm').addClass('is-invalid');
  }

  // Conditional License fields (only for driver)
  if (getEmpType() === 'driver') {
    check('licenseNumber', 'License Number');
    check('expire',        'Expiration Date');

    // Check file input
    const licenseFile = $('#licenseImage')[0].files[0];
    if (!licenseFile) {
      missing.push('License Image');
      $('#licenseImage').addClass('is-invalid');
    } else {
      $('#licenseImage').removeClass('is-invalid');
    }
  } else {
    // If assistant, remove invalid state just in case
    $('#licenseNumber, #expire, #licenseImage').removeClass('is-invalid');
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

  function showConfirmModal(formData) {
  const type = $('#empType').val();
  const roleLabels = {
    driver: 'Driver',
    assistant: 'Assistant',
    admin: 'Admin'
  };
  const typeLabel = roleLabels[type] || 'Employee';
  const name = ($('#empFName').val() + ' ' + $('#empLName').val()).trim();

  Swal.fire({
    icon: 'question',
    title: 'Confirm Registration',
    html:
      '<p class="mb-2">Please review the details before submitting:</p>' +
      '<div class="text-start bg-light rounded p-3">' +
        '<div><strong>Role:</strong> ' + typeLabel + '</div>' +
        '<div><strong>Name:</strong> ' + (name || '—') + '</div>' +
      '</div>',
    showCancelButton: true,
    confirmButtonText: '<i class="ri-check-line"></i> Yes, Register',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#696cff',
    cancelButtonColor: '#6c757d',
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) saveEmployee(formData);
  });
}


  // ===== Save =====
  function saveEmployee(formData) {
  $.ajax({
    url: 'ajax/employee_save_record.ajax.php',
    method: 'POST',
    data: formData,
    cache: false,
    contentType: false,
    processData: false,
    dataType: 'text',
    success: function (response) {
      const res = (response || '').trim();
      if (res === 'existing') {
        Swal.fire({ icon: 'info', title: 'Already Exists', text: 'This employee is already registered.', confirmButtonColor: '#696cff' });
      } else if (res === 'success') {
        Swal.fire({ icon: 'success', title: 'Registered!', text: 'Employee registered successfully.', confirmButtonColor: '#696cff' })
        .then(() => { window.location = 'employee-reg'; });
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: res, confirmButtonColor: '#696cff' });
      }
    },
    error: function () {
      Swal.fire({ icon: 'error', title: 'Network Error', text: 'Something went wrong while saving.', confirmButtonColor: '#696cff' });
    }
  });
}

});
