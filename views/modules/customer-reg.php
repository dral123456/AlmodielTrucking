<div class="row justify-content-center">
  <div class="col-12 col-xl-10">

    <div class="card">
      <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Customer Registration</h5>
          <p class="text-muted small mb-0">Register a new customer. Choose customer type to begin.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fs-6" id="custTypeBadge">
          <i class="ri-user-line me-1"></i> Individual
        </span>
      </div>

      <div class="card-body p-4">

        <!-- Hidden state -->
        <input type="hidden" id="customerType" value="individual">

        <!-- ===== CUSTOMER TYPE SELECTOR (TILE CARDS) ===== -->
        <div id="customerTypeChooser">
        <h6 class="text-uppercase text-muted mb-3">Select Customer Type</h6>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <div class="cust-type-tile active" data-type="individual">
              <div class="d-flex align-items-center">
                <div class="cust-type-icon bg-primary-subtle text-primary">
                  <i class="ri-user-3-line"></i>
                </div>
                <div class="ms-3">
                  <h6 class="mb-1">Individual</h6>
                  <p class="text-muted small mb-0">Single person customer (walk-in, personal account)</p>
                </div>
                <i class="ri-checkbox-circle-fill text-primary ms-auto cust-type-check"></i>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="cust-type-tile" data-type="company">
              <div class="d-flex align-items-center">
                <div class="cust-type-icon bg-info-subtle text-info">
                  <i class="ri-building-2-line"></i>
                </div>
                <div class="ms-3">
                  <h6 class="mb-1">Company</h6>
                  <p class="text-muted small mb-0">Business / corporate account with documents</p>
                </div>
                <i class="ri-checkbox-circle-fill text-primary ms-auto cust-type-check"></i>
              </div>
            </div>
          </div>
        </div>
        </div>

        <hr class="my-4" id="customerTypeDivider">

        <!-- ===== INDIVIDUAL FORM ===== -->
        <div id="individualForm">

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

          <div class="mb-4">
            <h6 class="text-uppercase text-muted mb-3">
              <i class="ri-map-pin-line me-1"></i> Address
            </h6>
            <div class="row">
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
            </div>
          </div>

        </div>

        <!-- ===== COMPANY FORM ===== -->
        <div id="companyForm" class="d-none">

          <div class="mb-4">
            <h6 class="text-uppercase text-muted mb-3">
              <i class="ri-building-line me-1"></i> Company Information
            </h6>
            <div class="row">
              <div class="col-12 col-md-8 mb-3">
                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-building-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="companyName" placeholder="Enter company name">
                </div>
              </div>
              <div class="col-12 col-md-4 mb-3">
                <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-contacts-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="contactPerson" placeholder="Authorized contact">
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
                <label class="form-label">Business Email <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-mail-line text-muted"></i>
                  <input type="email" class="form-control form-control-icon" id="emailCorp" placeholder="company@business.com">
                </div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Business Phone <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-phone-line text-muted"></i>
                  <input type="tel" class="form-control form-control-icon" id="phoneCorp" placeholder="Phone number">
                </div>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <h6 class="text-uppercase text-muted mb-3">
              <i class="ri-map-pin-line me-1"></i> Warehouse Address
            </h6>
            <div class="row">
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Province <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-map-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="provinceCorp" placeholder="Province">
                </div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">City / Municipality <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-building-2-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="cityCorp" placeholder="City">
                </div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Barangay <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-community-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="barangayCorp" placeholder="Barangay">
                </div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Street</label>
                <div class="form-icon">
                  <i class="ri-road-map-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="streetCorp" placeholder="Street">
                </div>
              </div>
              <div class="col-12 col-md-4 mb-3">
                <label class="form-label">Building / Unit No.</label>
                <div class="form-icon">
                  <i class="ri-home-line text-muted"></i>
                  <input type="text" class="form-control form-control-icon" id="houseCorp" placeholder="Bldg / Unit">
                </div>
              </div>
              <div class="col-12 mb-3">
                <div class="customer-map-panel">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                      <h6 class="text-uppercase text-muted mb-1">
                        <i class="ri-map-pin-2-line me-1"></i> Warehouse Pin
                      </h6>
                      <p class="text-muted small mb-0" id="customerMapStatus">Click the map to pin the company warehouse.</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary" id="customerCoordinateText">Not pinned</span>
                  </div>
                  <div class="customer-map-search mb-3">
                    <div class="input-group">
                      <span class="input-group-text"><i class="ri-search-line"></i></span>
                      <input type="text" class="form-control" id="warehouseMapSearch" placeholder="Search warehouse address or place">
                      <button type="button" class="btn btn-primary" id="warehouseMapSearchBtn">
                        Search
                      </button>
                    </div>
                  </div>
                  <div id="customerWarehouseMap"></div>
                  <input type="hidden" id="warehouseLatitude">
                  <input type="hidden" id="warehouseLongitude">
                </div>
              </div>
            </div>
          </div>

          <div class="mb-2">
            <h6 class="text-uppercase text-muted mb-3">
              <i class="ri-folder-line me-1"></i> Company Documents
            </h6>
            <div class="row">
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Business Registration <span class="text-danger">*</span></label>
                <input type="file" class="form-control file-upload-item" id="businessDoc" accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text">Accepted: PDF, JPG, PNG</div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Other Supporting Documents</label>
                <input type="file" class="form-control file-upload-item" id="otherDocs" accept=".pdf,.jpg,.jpeg,.png" multiple>
                <div class="form-text">You may upload multiple files</div>
              </div>
            </div>
          </div>

        </div>

        <!-- ===== ACCOUNT CREDENTIALS (shared) ===== -->
        <hr class="my-4">
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
              <div class="form-icon">
                <i class="ri-lock-2-line text-muted"></i>
                <input type="password" class="form-control form-control-icon" id="custPasswordConfirm" placeholder="Re-enter password" autocomplete="new-password">
              </div>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <hr class="my-4">
        <div class="d-flex justify-content-end gap-2">
          <button class="btn btn-light" type="button" id="btnResetCustomer">
            <i class="ri-refresh-line me-1"></i> Reset
          </button>
          <button class="btn btn-primary px-4" type="button" id="btnRegisterCustomer">
            <i class="ri-save-line me-1"></i> Register Customer
          </button>
        </div>

      </div>
    </div>

  </div>
</div>

<style>
  .cust-type-tile {
    cursor: pointer;
    border: 2px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
    background: var(--bs-body-bg);
    transition: all 0.2s ease;
    user-select: none;
  }
  .cust-type-tile:hover {
    border-color: var(--bs-primary);
  }
  .cust-type-tile.active {
    border-color: var(--bs-primary);
    background: var(--bs-primary-bg-subtle);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.12);
  }
  .cust-type-tile .cust-type-check {
    font-size: 1.5rem;
    opacity: 0;
    transition: opacity 0.2s ease;
  }
  .cust-type-tile.active .cust-type-check {
    opacity: 1;
  }
  .cust-type-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
  }

  #customerWarehouseMap {
    width: 100%;
    min-height: 380px;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .customer-map-search .input-group {
    max-width: 680px;
  }

  @media (max-width: 575.98px) {
    .customer-map-search .input-group {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
    }

    .customer-map-search .input-group .btn {
      grid-column: 1 / -1;
      width: 100%;
      margin-left: 0 !important;
      border-radius: 0.375rem !important;
      margin-top: 0.5rem;
    }

    #customerWarehouseMap {
      min-height: 320px;
    }
  }
</style>
