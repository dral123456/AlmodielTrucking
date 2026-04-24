<div class="row justify-content-center">
  <div class="col-12 col-lg-10 col-xl-9">
    <div class="card">
      <div class="card-body p-4">

        <h4 class="text-center mb-4">Customer Registration</h4>

        <div class="stepper">

          <!-- Step Indicators -->
          <div class="d-flex align-items-center mb-4 stepper-nav">
            <ul class="nav nav-pills d-flex gap-3 flex-wrap justify-content-center w-100" id="stepperNav">
              <li class="nav-item">
                <button class="nav-link active" type="button">1</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" type="button">2</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" type="button">3</button>
              </li>
              <li class="nav-item d-none" id="navStep4">
                <button class="nav-link" type="button">4</button>
              </li>
            </ul>
          </div>

          <!-- Progress Bar -->
          <div class="progress mb-4" style="height:5px;">
            <div class="progress-bar" role="progressbar" style="width:0%;height:5px;"></div>
          </div>

          <!-- Step Title -->
          <p class="text-center text-muted small mb-4" id="stepTitle">Step 1: Customer Type</p>

          <!-- Tab Content -->
          <div class="tab-content">

            <!-- STEP 1: Customer Type -->
            <div class="tab-pane show active" id="step-1">
              <div class="mb-3">
                <label class="form-label fw-semibold">Customer Type</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="customerType" id="typeIndividual" value="individual" checked>
                    <label class="form-check-label" for="typeIndividual">Individual</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="customerType" id="typeCompany" value="company">
                    <label class="form-check-label" for="typeCompany">Company</label>
                  </div>
                </div>
              </div>
            </div>

            <!-- STEP 2: Personal / Company Information -->
            <div class="tab-pane" id="step-2">
              <h6 class="text-uppercase text-muted mb-3" id="sectionPersonalLabel">Personal Information</h6>

              <!-- Company only -->
              <div class="row d-none" id="fieldCompanyName">
                <div class="col-12 mb-3">
                  <label class="form-label">Company Name</label>
                  <div class="form-icon">
                    <i class="ri-building-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="companyName">
                  </div>
                </div>
              </div>

              <!-- Individual only -->
              <div class="row" id="fieldIndividualName">
                <div class="col-12 col-md-5 mb-3">
                  <label class="form-label">First Name</label>
                  <div class="form-icon">
                    <i class="ri-user-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="firstName">
                  </div>
                </div>
                <div class="col-12 col-md-5 mb-3">
                  <label class="form-label">Last Name</label>
                  <div class="form-icon">
                    <i class="ri-user-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="lastName">
                  </div>
                </div>
                <div class="col-12 col-md-2 mb-3">
                  <label class="form-label">M.I.</label>
                  <div class="form-icon">
                    <i class="ri-font-size text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="middleInitial" maxlength="1">
                  </div>
                </div>
              </div>

              <!-- Company only -->
              <div class="row d-none" id="fieldContactPerson">
                <div class="col-12 mb-3">
                  <label class="form-label">Contact Person</label>
                  <div class="form-icon">
                    <i class="ri-contacts-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="contactPerson">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Email</label>
                  <div class="form-icon">
                    <i class="ri-mail-line text-muted"></i>
                    <input type="email" class="form-control form-control-icon" id="email">
                  </div>
                </div>
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Phone Number</label>
                  <div class="form-icon">
                    <i class="ri-phone-line text-muted"></i>
                    <input type="tel" class="form-control form-control-icon" id="phoneNumber">
                  </div>
                </div>
              </div>
            </div>

            <!-- STEP 3: Address -->
            <div class="tab-pane" id="step-3">
              <h6 class="text-uppercase text-muted mb-3">Address</h6>

              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Province</label>
                  <div class="form-icon">
                    <i class="ri-map-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="province">
                  </div>
                </div>
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">City</label>
                  <div class="form-icon">
                    <i class="ri-building-2-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="city">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Barangay</label>
                  <div class="form-icon">
                    <i class="ri-community-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="barangay">
                  </div>
                </div>
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Street</label>
                  <div class="form-icon">
                    <i class="ri-road-map-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="street">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-12 col-md-4 mb-3">
                  <label class="form-label">House Number</label>
                  <div class="form-icon">
                    <i class="ri-home-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="houseNumber">
                  </div>
                </div>
              </div>
            </div>

            <!-- STEP 4: Company Documents -->
            <div class="tab-pane" id="step-4">
              <h6 class="text-uppercase text-muted mb-3">Company Documents</h6>

              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Business Registration Document</label>
                  <input type="file" class="form-control file-upload-item" id="businessDoc" accept=".pdf,.jpg,.jpeg,.png">
                  <span class="text-danger small"></span>
                  <div class="form-text">Accepted formats: PDF, JPG, PNG</div>
                </div>
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Other Supporting Documents</label>
                  <input type="file" class="form-control file-upload-item" id="otherDocs" accept=".pdf,.jpg,.jpeg,.png" multiple>
                  <span class="text-danger small"></span>
                  <div class="form-text">You may upload multiple files</div>
                </div>
              </div>
            </div>

          </div>
          <!-- end tab-content -->

          <!-- Navigation -->
          <div class="d-flex justify-content-between mt-4">
            <button class="btn btn-outline-secondary previestab" type="button" id="btnBack">Back</button>
            <button class="btn btn-primary nexttab ms-auto" type="button" id="btnNext">Next</button>
          </div>

        </div>
        <!-- end stepper -->

      </div>
    </div>
  </div>
</div>

<style>
  .nav-pills .nav-link {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    background-color: var(--bs-secondary-bg, #e9ecef);
    color: var(--bs-secondary, #6c757d);
    border: none;
    transition: background-color 0.3s, color 0.3s;
  }
  .nav-pills .nav-link.active {
    background-color: var(--bs-primary, #696cff);
    color: #fff;
  }
  .nav-pills .nav-link.activeComplete {
    background-color: var(--bs-success, #71dd37);
    color: #fff;
  }
</style>

<!-- <script>
  const stepTitles = {
    1: 'Step 1: Customer Type',
    2: 'Step 2: Personal Information',
    3: 'Step 3: Address',
    4: 'Step 4: Company Documents'
  };

  function getCustomerType() {
    return document.querySelector('input[name="customerType"]:checked').value;
  }

  function getTotalSteps() {
    return getCustomerType() === 'company' ? 4 : 3;
  }

  function onCustomerTypeChange() {
    const isCompany = getCustomerType() === 'company';

    // Show/hide step 4 nav dot
    document.getElementById('navStep4').classList.toggle('d-none', !isCompany);

    // Update stepper's internal tab count awareness
    syncStepperTabs();
  }

  function applyCustomerTypeFields() {
    const isCompany = getCustomerType() === 'company';
    document.getElementById('sectionPersonalLabel').textContent = isCompany ? 'Company Information' : 'Personal Information';
    document.getElementById('fieldCompanyName').classList.toggle('d-none', !isCompany);
    document.getElementById('fieldIndividualName').classList.toggle('d-none', isCompany);
    document.getElementById('fieldContactPerson').classList.toggle('d-none', !isCompany);

    stepTitles[2] = isCompany ? 'Step 2: Company Information' : 'Step 2: Personal Information';
  }

  function syncStepperTabs() {
    const isCompany = getCustomerType() === 'company';
    const step4 = document.getElementById('step-4');
    if (isCompany) {
      step4.removeAttribute('data-hidden');
      step4.style.display = ''; // restore display
    } else {
      step4.setAttribute('data-hidden', 'true');
      step4.style.display = 'none';
    }
  }

  // Patch: update step title after stepper navigates
  const stepper = document.querySelector('.stepper');
  const observer = new MutationObserver(() => {
    const tabs = stepper.querySelectorAll('.tab-pane');
    tabs.forEach((tab, index) => {
      if (tab.classList.contains('active')) {
        const stepNum = index + 1;
        document.getElementById('stepTitle').textContent = stepTitles[stepNum] || '';

        // Apply field toggles when landing on step 2
        if (stepNum === 2) applyCustomerTypeFields();

        // Hide back on step 1, change Next to Register on last step
        const total = getTotalSteps();
        document.getElementById('btnBack').style.visibility = stepNum === 1 ? 'hidden' : 'visible';
        document.getElementById('btnNext').textContent = stepNum === total ? 'Register' : 'Next';
      }
    });
  });

  observer.observe(stepper, { attributes: true, subtree: true, attributeFilter: ['class'] });

  // Init
  syncStepperTabs();
  document.getElementById('btnBack').style.visibility = 'hidden';
</script> -->