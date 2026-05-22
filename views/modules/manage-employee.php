<?php
require_once "controllers/employee.controller.php";
require_once "models/employee.model.php";
require_once "controllers/salary.controller.php";
require_once "models/salary.model.php";

$employees = ControllerEmployee::ctrEmployeeList();
$salaryRows = ControllerSalary::ctrSalaryRows();
$salaryTrips = ControllerSalary::ctrDeliveredTripOptions();

function manageMoney($value) {
  return "PHP " . number_format((float) $value, 2);
}

function manageDate($value, $format = "M d, Y") {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime($value);
  return $timestamp ? date($format, $timestamp) : $value;
}
?>

<div class="manage-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Employee Management</h5>
        <p class="text-muted small mb-0">View drivers, assistants, contact details, and license information.</p>
      </div>
      <a href="employee-reg" class="btn btn-primary">
        <i class="ri-add-line me-1"></i> Add Employee
      </a>
    </div>
    <div class="card-body p-4">
      <div class="manage-toolbar mb-3">
        <div class="form-icon">
          <i class="ri-search-line text-muted"></i>
          <input type="text" class="form-control form-control-icon manage-search" data-target="#employeeManageTable" placeholder="Search employees">
        </div>
        <select class="form-select manage-status-filter" data-target="#employeeManageTable">
          <option value="all">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <div class="table-responsive">
        <table class="table align-middle manage-table" id="employeeManageTable">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Role</th>
              <th>Contact</th>
              <th>License</th>
              <th>Status</th>
              <th>Date Created</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($employees as $employee): ?>
              <?php
                $name = trim($employee["empFName"] . " " . $employee["empMI"] . " " . $employee["empLName"] . " " . $employee["empSuffix"]);
              ?>
              <tr
                data-status="<?php echo htmlspecialchars($employee["empStatus"]); ?>"
                data-id="<?php echo htmlspecialchars($employee["id"]); ?>"
                data-entity="employee"
                data-first-name="<?php echo htmlspecialchars($employee["empFName"]); ?>"
                data-last-name="<?php echo htmlspecialchars($employee["empLName"]); ?>"
                data-phone-number="<?php echo htmlspecialchars($employee["empPhoneNumber"]); ?>"
                data-email="<?php echo htmlspecialchars($employee["empEmail"]); ?>"
                data-emp-type="<?php echo htmlspecialchars($employee["empType"]); ?>"
              >
                <td>
                  <strong><?php echo htmlspecialchars($name); ?></strong>
                  <div class="small text-muted">ID #<?php echo htmlspecialchars($employee["id"]); ?></div>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars(ucfirst($employee["empType"])); ?></span>
                </td>
                <td>
                  <?php echo htmlspecialchars($employee["empPhoneNumber"]); ?>
                  <div class="small text-muted"><?php echo htmlspecialchars($employee["empEmail"]); ?></div>
                </td>
                <td>
                  <?php echo htmlspecialchars($employee["licenseNumber"] ?: "-"); ?>
                  <?php if (!empty($employee["licenseExpire"])): ?>
                    <div class="small text-muted">Expires <?php echo htmlspecialchars($employee["licenseExpire"]); ?></div>
                  <?php endif; ?>
                  <?php if (!empty($employee["licenseImage"])): ?>
                    <a class="small" href="uploads/licenses/<?php echo htmlspecialchars($employee["licenseImage"]); ?>" target="_blank">View license</a>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?php echo $employee["empStatus"] === "active" ? "bg-success-subtle text-success" : "bg-secondary-subtle text-secondary"; ?>">
                    <?php echo htmlspecialchars(ucfirst($employee["empStatus"])); ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($employee["dateCreated"]); ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light manage-edit"><i class="ri-edit-line"></i></button>
                    <button type="button" class="btn btn-light text-danger manage-archive"><i class="ri-archive-line"></i></button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="manage-empty text-center text-muted border rounded p-4 d-none">No employees found.</div>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Salary Management</h5>
        <p class="text-muted small mb-0">Create historical salary records, connect trip pay, and mark payroll as paid.</p>
      </div>
      <button type="button" class="btn btn-primary" id="salaryCreateBtn">
        <i class="ri-money-dollar-circle-line me-1"></i> Add Salary
      </button>
    </div>
    <div class="card-body p-4">
      <div class="manage-toolbar mb-3">
        <div class="form-icon">
          <i class="ri-search-line text-muted"></i>
          <input type="text" class="form-control form-control-icon salary-search" placeholder="Search salary records">
        </div>
        <select class="form-select salary-status-filter">
          <option value="all">All salary records</option>
          <option value="pending">Unpaid</option>
          <option value="paid">Paid</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <div class="table-responsive">
        <table class="table align-middle manage-table" id="salaryManageTable">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Trip Credit</th>
              <th>Pay Period</th>
              <th>Status</th>
              <th class="text-end">Gross</th>
              <th class="text-end">Deductions</th>
              <th class="text-end">Net</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($salaryRows as $salary): ?>
              <tr data-status="<?php echo htmlspecialchars($salary["status"]); ?>" data-salary-id="<?php echo (int) $salary["salaryID"]; ?>">
                <td>
                  <strong><?php echo htmlspecialchars($salary["employeeName"]); ?></strong>
                  <div class="small text-muted"><?php echo htmlspecialchars(ucfirst($salary["empType"])); ?> | <?php echo htmlspecialchars(ucfirst($salary["payType"])); ?></div>
                </td>
                <td>
                  <?php if (!empty($salary["tripID"])): ?>
                    <strong>Trip #<?php echo (int) $salary["tripID"]; ?></strong>
                    <div class="small text-muted">Credited booking #<?php echo (int) $salary["creditedBookingID"]; ?> | <?php echo number_format((float) $salary["creditedDistanceKm"], 2); ?> km</div>
                  <?php else: ?>
                    <span class="text-muted">No trip linked</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php echo htmlspecialchars(manageDate($salary["payPeriodStart"])); ?> - <?php echo htmlspecialchars(manageDate($salary["payPeriodEnd"])); ?>
                  <div class="small text-muted">Paid: <?php echo htmlspecialchars(manageDate($salary["datePaid"], "M d, Y h:i A")); ?></div>
                </td>
                <td>
                  <span class="badge <?php echo $salary["status"] === "paid" ? "bg-success-subtle text-success" : ($salary["status"] === "cancelled" ? "bg-secondary-subtle text-secondary" : "bg-warning-subtle text-warning"); ?>">
                    <?php echo htmlspecialchars($salary["status"] === "pending" ? "Unpaid" : ucfirst($salary["status"])); ?>
                  </span>
                </td>
                <td class="text-end"><?php echo manageMoney($salary["grossPay"]); ?></td>
                <td class="text-end"><?php echo manageMoney($salary["deductions"]); ?></td>
                <td class="text-end fw-semibold"><?php echo manageMoney($salary["netPay"]); ?></td>
                <td>
                  <?php if ($salary["status"] === "pending"): ?>
                    <button type="button" class="btn btn-sm btn-success salary-pay-btn">
                      <i class="ri-check-line me-1"></i> Pay
                    </button>
                  <?php else: ?>
                    <span class="text-muted small">No action</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="salary-empty text-center text-muted border rounded p-4 <?php echo empty($salaryRows) ? "" : "d-none"; ?>">No salary records found.</div>
    </div>
  </div>
</div>

<script>
  window.salaryEmployees = <?php echo json_encode(array_map(function ($employee) {
    return array(
      "id" => (int) $employee["id"],
      "name" => trim($employee["empFName"] . " " . $employee["empLName"]),
      "role" => $employee["empType"]
    );
  }, $employees), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.salaryTrips = <?php echo json_encode($salaryTrips, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<?php include __DIR__ . "/manage-style.php"; ?>
