<?php
require_once "controllers/truck.controller.php";
require_once "models/truck.model.php";

$trucks = ControllerTruck::ctrTruckManageList();
$drivers = ControllerTruck::ctrEmployeeListByType("driver");
$assistants = ControllerTruck::ctrEmployeeListByType("assistant");
?>

<div class="manage-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Truck Management</h5>
        <p class="text-muted small mb-0">View trucks, default crew assignments, capacity, mileage, and documents.</p>
      </div>
      <a href="truck-reg" class="btn btn-primary">
        <i class="ri-add-line me-1"></i> Add Truck
      </a>
    </div>
    <div class="card-body p-4">
      <div class="manage-toolbar mb-3">
        <div class="form-icon">
          <i class="ri-search-line text-muted"></i>
          <input type="text" class="form-control form-control-icon manage-search" data-target="#truckManageTable" placeholder="Search trucks">
        </div>
        <select class="form-select manage-status-filter" data-target="#truckManageTable">
          <option value="all">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <div class="table-responsive">
        <table class="table align-middle manage-table" id="truckManageTable">
          <thead>
            <tr>
              <th>Truck</th>
              <th>Specs</th>
              <th>Fuel / Mileage</th>
              <th>Default Crew</th>
              <th>Status</th>
              <th>Documents</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trucks as $truck): ?>
              <?php $crew = array_filter(explode("||", $truck["crew"] ?? "")); ?>
              <tr
                data-status="<?php echo htmlspecialchars($truck["status"]); ?>"
                data-id="<?php echo htmlspecialchars($truck["id"]); ?>"
                data-entity="truck"
                data-plate-number="<?php echo htmlspecialchars($truck["plateNumber"]); ?>"
                data-brand="<?php echo htmlspecialchars($truck["brand"]); ?>"
                data-type="<?php echo htmlspecialchars($truck["type"]); ?>"
                data-capacity="<?php echo htmlspecialchars($truck["capacity"]); ?>"
                data-fuel="<?php echo htmlspecialchars($truck["fuel"]); ?>"
                data-mileage="<?php echo htmlspecialchars($truck["mileage"]); ?>"
                data-driver-id="<?php echo htmlspecialchars($truck["driverID"] ?? ""); ?>"
                data-assistant-ids="<?php echo htmlspecialchars($truck["assistantIDs"] ?? ""); ?>"
              >
                <td>
                  <strong><?php echo htmlspecialchars($truck["plateNumber"]); ?></strong>
                  <div class="small text-muted"><?php echo htmlspecialchars($truck["brand"]); ?></div>
                </td>
                <td>
                  <?php echo htmlspecialchars($truck["type"]); ?>
                  <div class="small text-muted"><?php echo htmlspecialchars($truck["capacity"]); ?> kg capacity</div>
                </td>
                <td>
                  <?php echo htmlspecialchars($truck["fuel"]); ?> fuel
                  <div class="small text-muted"><?php echo htmlspecialchars($truck["mileage"]); ?> mileage</div>
                </td>
                <td>
                  <?php if ($crew): ?>
                    <?php foreach ($crew as $member): ?>
                      <div class="small"><?php echo htmlspecialchars($member); ?></div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span class="text-muted">No assigned crew</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?php echo $truck["status"] === "active" ? "bg-success-subtle text-success" : "bg-secondary-subtle text-secondary"; ?>">
                    <?php echo htmlspecialchars(ucfirst($truck["status"])); ?>
                  </span>
                </td>
                <td>
                  <?php if (!empty($truck["corDocument"])): ?>
                    <a class="btn btn-sm btn-light mb-1" href="uploads/<?php echo htmlspecialchars($truck["corDocument"]); ?>" target="_blank">COR</a>
                  <?php endif; ?>
                  <?php if (!empty($truck["otherDocument"])): ?>
                    <a class="btn btn-sm btn-light mb-1" href="uploads/<?php echo htmlspecialchars($truck["otherDocument"]); ?>" target="_blank">Other</a>
                  <?php endif; ?>
                  <?php if (empty($truck["corDocument"]) && empty($truck["otherDocument"])): ?>
                    <span class="text-muted">None</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light manage-edit"><i class="ri-edit-line"></i></button>
                    <button type="button" class="btn btn-light manage-crew"><i class="ri-team-line"></i></button>
                    <button type="button" class="btn btn-light text-danger manage-archive"><i class="ri-archive-line"></i></button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="manage-empty text-center text-muted border rounded p-4 d-none">No trucks found.</div>
    </div>
  </div>
</div>

<script>
  window.manageDrivers = <?php echo json_encode($drivers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.manageAssistants = <?php echo json_encode($assistants, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<?php include __DIR__ . "/manage-style.php"; ?>
