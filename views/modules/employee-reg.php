<!-- STEP 2: Personal / Company Information -->
<div class="tab-pane" id="step-2">
  <h6 class="text-uppercase text-muted mb-3" id="sectionPersonalLabel">Personal Information</h6>

  <!-- Company only -->
  <div class="row d-none" id="fieldCompanyName">
    <div class="col-12 mb-3">
      <label class="form-label">Company Name</label>
      <div class="form-icon">
        <i class="ri-building-line text-muted"></i>
        <input type="text" class="form-control form-control-icon">
      </div>
    </div>
  </div>

  <!-- Individual only -->
  <div class="row" id="fieldIndividualName">
    <div class="col-12 col-md-5 mb-3">
      <label class="form-label">First Name</label>
      <div class="form-icon">
        <i class="ri-user-line text-muted"></i>
        <input type="text" class="form-control form-control-icon">
      </div>
    </div>
    <div class="col-12 col-md-5 mb-3">
      <label class="form-label">Last Name</label>
      <div class="form-icon">
        <i class="ri-user-line text-muted"></i>
        <input type="text" class="form-control form-control-icon">
      </div>
    </div>
    <div class="col-12 col-md-2 mb-3">
      <label class="form-label">M.I.</label>
      <div class="form-icon">
        <i class="ri-font-size text-muted"></i>
        <input type="text" class="form-control form-control-icon" maxlength="2">
      </div>
    </div>
  </div>

  <!-- Company only -->
  <div class="row d-none" id="fieldContactPerson">
    <div class="col-12 mb-3">
      <label class="form-label">Contact Person</label>
      <div class="form-icon">
        <i class="ri-contacts-line text-muted"></i>
        <input type="text" class="form-control form-control-icon">
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12 col-md-6 mb-3">
      <label class="form-label">Email</label>
      <div class="form-icon">
        <i class="ri-mail-line text-muted"></i>
        <input type="email" class="form-control form-control-icon">
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <label class="form-label">Phone Number</label>
      <div class="form-icon">
        <i class="ri-phone-line text-muted"></i>
        <input type="tel" class="form-control form-control-icon">
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
        <input type="text" class="form-control form-control-icon">
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <label class="form-label">City</label>
      <div class="form-icon">
        <i class="ri-building-2-line text-muted"></i>
        <input type="text" class="form-control form-control-icon">
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12 col-md-6 mb-3">
      <label class="form-label">Barangay</label>
      <div class="form-icon">
        <i class="ri-community-line text-muted"></i>
        <input type="text" class="form-control form-control-icon">
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <label class="form-label">Street</label>
      <div class="form-icon">
        <i class="ri-road-map-line text-muted"></i>
        <input type="text" class="form-control form-control-icon">
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12 col-md-4 mb-3">
      <label class="form-label">House Number</label>
      <div class="form-icon">
        <i class="ri-home-line text-muted"></i>
        <input type="text" class="form-control form-control-icon">
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
      <input type="file" class="form-control file-upload-item" accept=".pdf,.jpg,.jpeg,.png">
      <span class="text-danger small"></span>
      <div class="form-text">Accepted formats: PDF, JPG, PNG</div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <label class="form-label">Other Supporting Documents</label>
      <input type="file" class="form-control file-upload-item" accept=".pdf,.jpg,.jpeg,.png" multiple>
      <span class="text-danger small"></span>
      <div class="form-text">You may upload multiple files</div>
    </div>
  </div>
</div>