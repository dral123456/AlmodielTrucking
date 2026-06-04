<?php
require_once "controllers/truck.controller.php";
require_once "models/truck.model.php";

$drivers = ControllerTruck::ctrEmployeeListByType("driver");
$assistants = ControllerTruck::ctrEmployeeListByType("assistant");
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-10">
    <div class="card">
      <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Truck Registration</h5>
          <p class="text-muted small mb-0">Register a truck and assign its driver and two assistants.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fs-6">
          <i class="ri-truck-line me-1"></i> Truck
        </span>
      </div>

      <div class="card-body p-4">
        <div class="mb-4">
          <h6 class="text-uppercase text-muted mb-3">
            <i class="ri-truck-line me-1"></i> Truck Information
          </h6>
          <div class="row">
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Plate Number <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-hashtag text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="truckPlateNumber" maxlength="20" placeholder="ABC123">
              </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Brand <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-price-tag-3-line text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="truckBrand" maxlength="20" placeholder="Isuzu">
              </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Type <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-truck-line text-muted"></i>
                <input type="text" class="form-control form-control-icon" id="truckType" maxlength="20" placeholder="6W">
              </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Capacity <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-scales-3-line text-muted"></i>
                <input type="number" class="form-control form-control-icon" id="truckCapacity" min="0" step="0.01" placeholder="5000">
              </div>
              <div class="form-text">Enter capacity as a number.</div>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Fuel <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-gas-station-line text-muted"></i>
                <input type="number" class="form-control form-control-icon" id="truckFuel" min="0" step="1" placeholder="50">
              </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Mileage <span class="text-danger">*</span></label>
              <div class="form-icon">
                <i class="ri-dashboard-3-line text-muted"></i>
                <input type="number" class="form-control form-control-icon" id="truckMileage" min="0" step="1" placeholder="12000">
              </div>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <div class="mb-4">
          <h6 class="text-uppercase text-muted mb-3">
            <i class="ri-file-image-line me-1"></i> Truck Documents
          </h6>
          <div class="row">
            <div class="col-12 col-md-6 mb-3">
              <label class="form-label">COR Image <span class="text-danger">*</span></label>
              <input type="file" class="form-control" id="truckCorDocument" accept="image/png,image/jpeg,image/webp">
              <div class="form-text">Certificate of Registration image. Accepted: JPG, PNG, WEBP.</div>
            </div>
            <div class="col-12 col-md-6 mb-3">
              <label class="form-label">OR / Other Truck Document</label>
              <input type="file" class="form-control" id="truckOtherDocument" accept="image/png,image/jpeg,image/webp">
              <div class="form-text">Optional image, such as Official Receipt, insurance, permit, or emission test.</div>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <div class="mb-2">
          <h6 class="text-uppercase text-muted mb-3">
            <i class="ri-team-line me-1"></i> Employee Assignment
          </h6>
          <div class="row">
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Driver <span class="text-danger">*</span></label>
              <select class="form-select" id="truckDriver">
                <option value="">Select driver</option>
                <?php foreach ($drivers as $driver): ?>
                  <option value="<?php echo htmlspecialchars($driver["id"]); ?>">
                    <?php echo htmlspecialchars(trim($driver["empFName"] . " " . $driver["empLName"])); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Assistant 1 <span class="text-danger">*</span></label>
              <select class="form-select" id="truckAssistant1">
                <option value="">Select assistant 1</option>
                <?php foreach ($assistants as $assistant): ?>
                  <option value="<?php echo htmlspecialchars($assistant["id"]); ?>">
                    <?php echo htmlspecialchars(trim($assistant["empFName"] . " " . $assistant["empLName"])); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label class="form-label">Assistant 2 <span class="text-danger">*</span></label>
              <select class="form-select" id="truckAssistant2">
                <option value="">Select assistant 2</option>
                <?php foreach ($assistants as $assistant): ?>
                  <option value="<?php echo htmlspecialchars($assistant["id"]); ?>">
                    <?php echo htmlspecialchars(trim($assistant["empFName"] . " " . $assistant["empLName"])); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <hr class="my-4">
        <div class="d-flex justify-content-end gap-2">
          <button class="btn btn-light" type="button" id="truckBtnReset">
            <i class="ri-refresh-line me-1"></i> Reset
          </button>
          <button class="btn btn-primary px-4" type="button" id="truckBtnRegister">
            <i class="ri-save-line me-1"></i> Register Truck
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
