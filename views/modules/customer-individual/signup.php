<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">

      <div class="card mt-5 pb-3">
        <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h5 class="mb-0">Customer Registration</h5>
          </div>
          <a href="customer-login" class="btn btn-outline-secondary btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Back to Login
          </a>
        </div>

        <div class="card-body">

          <!-- ===== STEP INDICATOR ===== -->
          <div class="reg-stepper d-flex align-items-center justify-content-center gap-0 mb-4">
            <div class="reg-step active" id="stepDot1">
              <div class="reg-step-circle">1</div>
              <div class="reg-step-label">Personal Info</div>
            </div>
            <div class="reg-step-line" id="stepLine1"></div>
            <div class="reg-step" id="stepDot2">
              <div class="reg-step-circle">2</div>
              <div class="reg-step-label">Address</div>
            </div>
            <div class="reg-step-line" id="stepLine2"></div>
            <div class="reg-step" id="stepDot3">
              <div class="reg-step-circle">3</div>
              <div class="reg-step-label">Credentials</div>
            </div>
          </div>

          <!-- ===== STEP 1: PERSONAL & CONTACT ===== -->
          <div id="regStep1">

            <div class="mb-4">
              <h6 class="text-uppercase text-muted mb-3">
                <i class="ri-id-card-line me-1"></i> Personal Information
              </h6>
              <div class="row">
                <div class="col-12 col-md-5 mb-3">
                  <label class="form-label">First Name <span class="text-danger">*</span></label>
                  <div class="form-icon">
                    <i class="ri-user-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="firstName" placeholder="First name">
                  </div>
                </div>
                <div class="col-12 col-md-5 mb-3">
                  <label class="form-label">Last Name <span class="text-danger">*</span></label>
                  <div class="form-icon">
                    <i class="ri-user-line text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="lastName" placeholder="Last name">
                  </div>
                </div>
                <div class="col-12 col-md-2 mb-3">
                  <label class="form-label">M.I.</label>
                  <div class="form-icon">
                    <i class="ri-font-size text-muted"></i>
                    <input type="text" class="form-control form-control-icon" id="middleInitial" maxlength="1" placeholder="M">
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h6 class="text-uppercase text-muted mb-3">
                <i class="ri-contacts-book-line me-1"></i> Contact Information
              </h6>
              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Email</label>
                  <div class="form-icon">
                    <i class="ri-mail-line text-muted"></i>
                    <input type="email" class="form-control form-control-icon" id="emailIndiv" placeholder="email@example.com">
                  </div>
                </div>
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                  <div class="form-icon">
                    <i class="ri-phone-line text-muted"></i>
                    <input type="tel" class="form-control form-control-icon" id="phoneIndiv" placeholder="09XXXXXXXXX">
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
              <button class="btn btn-primary px-4" type="button" id="btnStep1Next">
                Next <i class="ri-arrow-right-line ms-1"></i>
              </button>
            </div>
          </div>

          <!-- ===== STEP 2: ADDRESS + MAP ===== -->
          <div id="regStep2" style="display:none;">

            <div class="mb-3">
              <h6 class="text-uppercase text-muted mb-3">
                <i class="ri-map-pin-line me-1"></i> Address
              </h6>

              <!-- Map container with search bar inside -->
              <div style="position:relative; border-radius:0.5rem; overflow:hidden; border:1px solid var(--bs-border-color);">
                <div id="mapSearch" style="
                  position:absolute;
                  top:10px;
                  left:50%;
                  transform:translateX(-50%);
                  z-index:1000;
                  display:flex;
                  gap:6px;
                  width:70%;
                  min-width:220px;
                ">
                  <input type="text" id="mapSearchInput" placeholder="Search location…" style="
                    flex:1;
                    padding:6px 12px;
                    border-radius:6px;
                    border:1px solid #ccc;
                    font-size:13px;
                    box-shadow:0 2px 6px rgba(0,0,0,0.15);
                  ">
                  <button type="button" id="mapSearchBtn" style="
                    padding:6px 12px;
                    border-radius:6px;
                    border:none;
                    background:#696cff;
                    color:#fff;
                    font-size:13px;
                    cursor:pointer;
                    box-shadow:0 2px 6px rgba(0,0,0,0.15);
                  "><i class="ri-search-line"></i></button>
                </div>
                <div id="regMap" style="height:320px; width:100%;"></div>
              </div>
              <div class="form-text mt-1"><i class="ri-information-line me-1"></i>Click anywhere on the map to pin your location and auto-fill the fields below.</div>
            </div>

            <input type="hidden" id="lat" name="lat">
            <input type="hidden" id="lng" name="lng">

            <div class="row mt-3">
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Province <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-map-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="provinceIndiv" placeholder="Province">
                </div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">City / Municipality <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-building-2-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="cityIndiv" placeholder="City">
                </div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Barangay <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-community-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="barangayIndiv" placeholder="Barangay">
                </div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Street</label>
                <div class="form-icon">
                  <i class="ri-road-map-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="streetIndiv" placeholder="Street">
                </div>
              </div>
              <div class="col-12 col-md-4 mb-3">
                <label class="form-label">House / Unit No.</label>
                <div class="form-icon">
                  <i class="ri-home-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="houseIndiv" placeholder="House No.">
                </div>
              </div>
              <div class="col-12 mb-3">
                <label class="form-label">Description / Landmark</label>
                <div class="desc-field-wrap">
                  <i class="ri-sticky-note-line text-muted desc-icon"></i>
                  <textarea class="form-control desc-textarea" id="locationDescription" rows="2"
                    placeholder="e.g. Near the church, blue gate, beside 7-Eleven…"
                    style="resize:none;"></textarea>
                </div>
                <div class="form-text">Optional — helps identify your location more precisely.</div>
              </div>
            </div>

            <div class="d-flex justify-content-between gap-2 mt-3">
              <button class="btn btn-light" type="button" id="btnStep2Prev">
                <i class="ri-arrow-left-line me-1"></i> Previous
              </button>
              <button class="btn btn-primary px-4" type="button" id="btnStep2Next">
                Next <i class="ri-arrow-right-line ms-1"></i>
              </button>
            </div>
          </div>

          <!-- ===== STEP 3: ACCOUNT CREDENTIALS ===== -->
          <div id="regStep3" style="display:none;">

            <div class="mb-2">
              <h6 class="text-uppercase text-muted mb-3">
                <i class="ri-shield-keyhole-line me-1"></i> Account Credentials
              </h6>
              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Password <span class="text-danger">*</span></label>
                  <div class="form-icon position-relative">
                    <i class="ri-lock-2-line text-muted"></i>
                    <input type="password" class="form-control form-control-icon pe-5" id="custPassword" placeholder="Enter password" autocomplete="new-password">
                    <button type="button" class="btn btn-link p-0 text-muted position-absolute" id="toggleCustPassword"
                            style="right:.75rem; top:50%; transform:translateY(-50%); text-decoration:none;">
                      <i class="ri-eye-line"></i>
                    </button>
                  </div>
                  <div class="form-text">Minimum 6 characters.</div>
                </div>
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                  <div class="form-icon position-relative">
                    <i class="ri-lock-2-line text-muted"></i>
                    <input type="password" class="form-control form-control-icon pe-5" id="custPasswordConfirm" placeholder="Re-enter password" autocomplete="new-password">
                    <button type="button" class="btn btn-link p-0 text-muted position-absolute" id="toggleCustPasswordConfirm"
                            style="right:.75rem; top:50%; transform:translateY(-50%); text-decoration:none;">
                      <i class="ri-eye-line"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between gap-2 mt-3">
              <button class="btn btn-light" type="button" id="btnStep3Prev">
                <i class="ri-arrow-left-line me-1"></i> Previous
              </button>
              <div class="d-flex gap-2">
                <button class="btn btn-light" type="button" id="btnResetCustomer">
                  <i class="ri-refresh-line me-1"></i> Reset
                </button>
                <button class="btn btn-primary px-4" type="button" id="btnRegisterCustomer">
                  <i class="ri-save-line me-1"></i> Register
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .reg-stepper {
    padding: 0 1rem;
  }
  .reg-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
  }
  .reg-step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.25s ease;
  }
  .reg-step.active .reg-step-circle {
    border-color: #696cff;
    background: #696cff;
    color: #fff;
  }
  .reg-step.done .reg-step-circle {
    border-color: #696cff;
    background: #eeedfe;
    color: #696cff;
  }
  .reg-step-label {
    font-size: 11px;
    color: var(--bs-secondary-color);
    white-space: nowrap;
  }
  .reg-step.active .reg-step-label {
    color: #696cff;
    font-weight: 600;
  }
  .reg-step-line {
    height: 2px;
    width: 80px;
    background: var(--bs-border-color);
    margin-bottom: 18px;
    transition: background 0.25s ease;
  }
  .reg-step-line.done {
    background: #696cff;
  }
  .card {
    overflow-x: hidden;
  }
  .card-header {
    border-bottom: none;
  }

  /* Description / Landmark field */
  .desc-field-wrap {
    position: relative;
    display: flex;
    align-items: flex-start;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.375rem;
    background: var(--bs-body-bg);
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }
  .desc-field-wrap:focus-within {
    border-color: #696cff;
    box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
  }
  .desc-icon {
    padding: 0.5rem 0.65rem 0;
    font-size: 1rem;
    pointer-events: none;
    flex-shrink: 0;
  }
  .desc-textarea {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    padding-left: 0;
    border-radius: 0 0.375rem 0.375rem 0;
    flex: 1;
  }
  .desc-textarea:focus {
    outline: none;
    box-shadow: none !important;
  }
</style>