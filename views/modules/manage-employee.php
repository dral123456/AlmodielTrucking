<?php
require_once "controllers/employee.controller.php";
require_once "models/employee.model.php";

$employees = ControllerEmployee::ctrEmployeeList();
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
</div>

<?php include __DIR__ . "/manage-style.php"; ?>
