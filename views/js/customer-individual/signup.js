$(document).ready(function () {

    function getCustomerType() {
      return "individual";
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
    }
  
    // Tile click → set hidden value + repaint
    $(document).on('click', '.cust-type-tile', function () {
      $('#customerType').val($(this).data('type'));
      applyCustomerType();
    });
  
    // Reset
    $(document).on('click', '#btnResetCustomer', function () {
      $('#individualForm input, #companyForm input').val('');
      $('#custPassword, #custPasswordConfirm').val('');
      $('.is-invalid').removeClass('is-invalid');
      $('#customerType').val('individual');
      applyCustomerType();
    });
  
    // Show/hide password
    $(document).on('click', '#toggleCustPassword', function () {
      const $pwd = $('#custPassword');
      const isHidden = $pwd.attr('type') === 'password';
      $pwd.attr('type', isHidden ? 'text' : 'password');
      $(this).find('i').toggleClass('ri-eye-line', !isHidden).toggleClass('ri-eye-off-line', isHidden);
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
    applyCustomerType();
  
  
    // ===== Validation =====
    function validateInputs() {
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
  
      check('firstName',     'First Name');
      check('lastName',      'Last Name');
      check('phoneIndiv',    'Phone Number');
      check('provinceIndiv', 'Province');
      check('cityIndiv',     'City / Municipality');
      check('barangayIndiv', 'Barangay');
    
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
  