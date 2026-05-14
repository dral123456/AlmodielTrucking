<div class="row justify-content-center">
  <div class="col-12 col-xl-10">

    <div class="card">
      <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Employee Registration</h5>
          <p class="text-muted small mb-0">Register a new employee. Choose role to begin.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fs-6" id="empTypeBadge">
          <i class="ri-steering-2-line me-1"></i> Driver
        </span>
      </div>

      <div class="card-body p-4">

        <!-- Hidden state -->
        <input type="hidden" id="empType" value="driver">

        <!-- ===== ROLE SELECTOR (TILE CARDS) ===== -->
        <h6 class="text-uppercase text-muted mb-3">Select Role</h6>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <div class="emp-type-tile active" data-type="driver">
              <div class="d-flex align-items-center">
                <div class="emp-type-icon bg-primary-subtle text-primary">
                  <i class="ri-steering-2-line"></i>
                </div>
                <div class="ms-3">
                  <h6 class="mb-1">Driver</h6>
                  <p class="text-muted small mb-0">Operates company trucks and delivery vehicles</p>
                </div>
                <i class="ri-checkbox-circle-fill text-primary ms-auto emp-type-check"></i>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="emp-type-tile" data-type="assistant">
              <div class="d-flex align-items-center">
                <div class="emp-type-icon bg-info-subtle text-info">
                  <i class="ri-user-2-line"></i>
                </div>
                <div class="ms-3">
                  <h6 class="mb-1">Assistant</h6>
                  <p class="text-muted small mb-0">Supports loading, unloading and field operations</p>
                </div>
                <i class="ri-checkbox-circle-fill text-primary ms-auto emp-type-check"></i>
              </div>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <!-- ===== PERSONAL INFORMATION ===== -->
        <div class="mb-4">
          <h6 class="text-uppercase text-muted mb-3">
            <i class="ri-id-card-line me-1"></i> Personal Information
          </h6>
          <div class="row">
            <div class="col-12 col-md-5 mb-3">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-user-line text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="empFName" placeholder="First name">
              </div>
            </div>
            <div class="col-12 col-md-5 mb-3">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-user-line text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="empLName" placeholder="Last name">
              </div>
            </div>
            <div class="col-12 col-md-2 mb-3">
              <label class="form-label">M.I.</label>
              <div class="form-icon">
                <i class="ri-font-size text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="empMI" maxlength="1" placeholder="M">
              </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Suffix</label>
              <div class="form-icon">
                <i class="ri-text text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="empSuffix" placeholder="e.g. Jr., Sr., III">
              </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Birth Date <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-calendar-line text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="empBirthDate" placeholder="Select birth date">
              </div>
            </div>
          </div>
        </div>


       <div class="mb-4">
          <h6 class="text-uppercase text-muted mb-3">
            <i class="ri-id-card-line me-1"></i> License Information
          </h6>
          <div class="row">
              
            <div class="col-12 col-md-6 mb-3">
              <label class="form-label">License Number<span class="text-danger">*</span></label>
              <div class="form-icon col">
                <i class="ri-mail-line text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="licenseNumber" placeholder="f01-12-00000000">
              </div>
            </div>


              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Expiration Date<span class="text-danger">*</span></label>
              <div class="form-icon col">
                <i class="ri-mail-line text-muted"></i>
                <input type="date" class="form-control form-control-icon" id="expire" placeholder="Expiration Date">
              </div>
              </div>
              
          </div>
          <br>
          <div class="row">
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">License Image<span class="text-danger">*</span></label>
                <div class="form-icon">
                <i class="ri-mail-line text-muted"></i>
                <input type="file" class="form-control form-control-icon" id="licenseImage" placeholder="Upload License Picture">
              </div>
              </div>
          </div>
       </div>

        <!-- ===== CONTACT INFORMATION ===== -->
        <div class="mb-4">
          <h6 class="text-uppercase text-muted mb-3">
            <i class="ri-contacts-book-line me-1"></i> Contact Information
          </h6>
          <div class="row">
            <div class="col-12 col-md-6 mb-3">
              <label class="form-label">Phone Number <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-phone-line text-muted"></i>
                <input type="tel" class="form-control form-control-icon" id="empPhoneNumber" maxlength="11" placeholder="09XXXXXXXXX">
              </div>
            </div>
            <div class="col-12 col-md-6 mb-3">
              <label class="form-label">Email</label>
              <div class="form-icon">
                <i class="ri-mail-line text-muted"></i>
                <input type="email" class="form-control form-control-icon" id="empEmail" placeholder="email@example.com">
              </div>
            </div>
          </div>
        </div>

        <!-- ===== ACCOUNT CREDENTIALS ===== -->
        <div class="mb-2">
          <h6 class="text-uppercase text-muted mb-3">
            <i class="ri-shield-keyhole-line me-1"></i> Account Credentials
          </h6>
          <div class="row">
            <div class="col-12 col-md-6 mb-3">
              <label class="form-label">Password <span class="text-danger">*</span></label>
              <div class="form-icon position-relative">
                <i class="ri-lock-2-line text-muted"></i>
                <input type="password" class="form-control form-control-icon pe-5" id="empPassword" placeholder="Enter password" autocomplete="new-password">
                <button type="button" class="btn btn-link p-0 text-muted position-absolute" id="togglePassword"
                        style="right:.75rem; top:50%; transform:translateY(-50%); text-decoration:none;">
                  <i class="ri-eye-line"></i>
                </button>
              </div>
              <div class="form-text">Minimum 6 characters.</div>
            </div>
            <div class="col-12 col-md-6 mb-3">
              <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-lock-2-line text-muted"></i>
                <input type="password" class="form-control form-control-icon" id="empPasswordConfirm" placeholder="Re-enter password" autocomplete="new-password">
              </div>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <hr class="my-4">
        <div class="d-flex justify-content-end gap-2">
          <button class="btn btn-light" type="button" id="empBtnReset">
            <i class="ri-refresh-line me-1"></i> Reset
          </button>
          <button class="btn btn-primary px-4" type="button" id="empBtnRegister">
            <i class="ri-save-line me-1"></i> Register Employee
          </button>
        </div>

      </div>
    </div>

  </div>
</div>

<style>
  .emp-type-tile {
    cursor: pointer;
    border: 2px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
    background: var(--bs-body-bg);
    transition: all 0.2s ease;
    user-select: none;
  }
  .emp-type-tile:hover {
    border-color: var(--bs-primary);
  }
  .emp-type-tile.active {
    border-color: var(--bs-primary);
    background: var(--bs-primary-bg-subtle);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.12);
  }
  .emp-type-tile .emp-type-check {
    font-size: 1.5rem;
    opacity: 0;
    transition: opacity 0.2s ease;
  }
  .emp-type-tile.active .emp-type-check {
    opacity: 1;
  }
  .emp-type-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
  }
</style>
