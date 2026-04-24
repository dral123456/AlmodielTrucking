$(document).ready(function () {

  const stepTitles = {
    1: 'Step 1: Customer Type',
    2: 'Step 2: Personal Information',
    3: 'Step 3: Address',
    4: 'Step 4: Company Documents'
  };

  function getCustomerType() {
    return $('input[name="customerType"]:checked').val();
  }

  function getTotalSteps() {
    return getCustomerType() === 'company' ? 4 : 3;
  }

  function onCustomerTypeChange() {
    const isCompany = getCustomerType() === 'company';
    $('#navStep4').toggleClass('d-none', !isCompany);
    syncStepperTabs();
  }

  function applyCustomerTypeFields() {
    const isCompany = getCustomerType() === 'company';
    $('#sectionPersonalLabel').text(isCompany ? 'Company Information' : 'Personal Information');
    $('#fieldCompanyName').toggleClass('d-none', !isCompany);
    $('#fieldIndividualName').toggleClass('d-none', isCompany);
    $('#fieldContactPerson').toggleClass('d-none', !isCompany);
    stepTitles[2] = isCompany ? 'Step 2: Company Information' : 'Step 2: Personal Information';
  }

  function syncStepperTabs() {
    const isCompany = getCustomerType() === 'company';
    const $step4 = $('#step-4');
    if (isCompany) {
      $step4.removeAttr('data-hidden').css('display', '');
    } else {
      $step4.attr('data-hidden', 'true').css('display', 'none');
    }
  }

  // Patch: update step title after stepper navigates
  const stepper = document.querySelector('.stepper');
  const observer = new MutationObserver(() => {
    $('.tab-pane').each(function (index) {
      if ($(this).hasClass('active')) {
        const stepNum = index + 1;
        $('#stepTitle').text(stepTitles[stepNum] || '');
        if (stepNum === 2) applyCustomerTypeFields();
        const total = getTotalSteps();
        $('#btnBack').css('visibility', stepNum === 1 ? 'hidden' : 'visible');

        // Bind register on last step
        if (stepNum === total) {
          $('#btnNext').text('Register').off('click.register').on('click.register', function () {
            saveCustomer();
          });
        } else {
          $('#btnNext').text('Next').off('click.register');
        }
      }
    });
  });

  observer.observe(stepper, { attributes: true, subtree: true, attributeFilter: ['class'] });

  // Bind radio change
  $('input[name="customerType"]').on('change', onCustomerTypeChange);

  // Init
  syncStepperTabs();
  $('#btnBack').css('visibility', 'hidden');

  function saveCustomer() {
    const isCompany = getCustomerType() === 'company';

    const formData = new FormData();
    formData.append('customerType', getCustomerType());

    if (isCompany) {
      formData.append('companyName', $('#companyName').val());
      formData.append('contactPerson', $('#contactPerson').val());
    } else {
      formData.append('firstName', $('#firstName').val());
      formData.append('lastName', $('#lastName').val());
      formData.append('middleInitial', $('#middleInitial').val());
    }

    formData.append('email', $('#email').val());
    formData.append('phoneNumber', $('#phoneNumber').val());
    formData.append('province', $('#province').val());
    formData.append('city', $('#city').val());
    formData.append('barangay', $('#barangay').val());
    formData.append('street', $('#street').val());
    formData.append('houseNumber', $('#houseNumber').val());

    if (isCompany) {
      const bizDoc = $('#businessDoc')[0].files[0];
      const otherDocs = $('#otherDocs')[0].files;
      if (bizDoc) formData.append('businessDoc', bizDoc);
      for (let i = 0; i < otherDocs.length; i++) {
        formData.append('otherDocs[]', otherDocs[i]);
      }
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
        console.log('Success:', response);

        if (response.trim() === 'success') {
          alert('Customer registered successfully!');
          
          setTimeout(() => {
            location.reload();
          }, 1000); // small delay for UX
        } else if (response.trim() === 'existing') {
          alert('Customer already exists!');
        } else {
          alert('Error saving customer.');
        }
      },
      error: function () {
        console.error('Something went wrong.');
        // handle error
      }
    });
  }

});