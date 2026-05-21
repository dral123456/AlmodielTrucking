<?php
require_once "controllers/report.controller.php";
require_once "models/report.model.php";

$summary = ControllerReport::ctrSummary();
$billingRows = ControllerReport::ctrBillingRows();
$expenseRows = ControllerReport::ctrExpenseRows();
$staffRows = ControllerReport::ctrStaffRows();
$salaryRows = ControllerReport::ctrSalaryRows();

function reportMoney($value) {
    return "PHP " . number_format((float) $value, 2);
}

function reportText($value, $fallback = "-") {
    $value = trim((string) $value);
    return htmlspecialchars($value !== "" ? $value : $fallback);
}

function reportDate($value) {
    if (!$value) {
        return "-";
    }

    $timestamp = strtotime($value);
    return $timestamp ? date("M d, Y h:i A", $timestamp) : $value;
}

function reportStaffName($employee) {
    return trim($employee["empFName"] . " " . $employee["empMI"] . " " . $employee["empLName"] . " " . $employee["empSuffix"]);
}
?>

<div class="reports-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Reports</h5>
        <p class="text-muted small mb-0">Review billing, expenses, staff list, and staff salary records.</p>
      </div>
      <span class="badge bg-primary-subtle text-primary fs-6">
        <i class="ri-file-chart-line me-1"></i> Operations Reports
      </span>
    </div>

    <div class="card-body p-4">
      <div class="reports-summary-grid mb-4">
        <div class="report-stat-card">
          <div class="report-stat-icon bg-success-subtle text-success"><i class="ri-bill-line"></i></div>
          <div>
            <span class="text-muted small">Billing</span>
            <h4 class="mb-0"><?php echo reportMoney($summary["billingTotal"]); ?></h4>
            <small class="text-muted"><?php echo (int) $summary["bookingCount"]; ?> booking(s)</small>
          </div>
        </div>
        <div class="report-stat-card">
          <div class="report-stat-icon bg-danger-subtle text-danger"><i class="ri-wallet-3-line"></i></div>
          <div>
            <span class="text-muted small">Expenses</span>
            <h4 class="mb-0"><?php echo reportMoney($summary["expenseTotal"]); ?></h4>
            <small class="text-muted"><?php echo $summary["hasExpenseTable"] ? "From expense records" : "No expense table yet"; ?></small>
          </div>
        </div>
        <div class="report-stat-card">
          <div class="report-stat-icon bg-info-subtle text-info"><i class="ri-team-line"></i></div>
          <div>
            <span class="text-muted small">Staff</span>
            <h4 class="mb-0"><?php echo (int) $summary["activeStaffCount"]; ?> active</h4>
            <small class="text-muted"><?php echo (int) $summary["staffCount"]; ?> total staff</small>
          </div>
        </div>
        <div class="report-stat-card">
          <div class="report-stat-icon bg-warning-subtle text-warning"><i class="ri-money-dollar-circle-line"></i></div>
          <div>
            <span class="text-muted small">Staff Salary</span>
            <h4 class="mb-0"><?php echo reportMoney($summary["salaryTotal"]); ?></h4>
            <small class="text-muted"><?php echo $summary["hasSalaryTable"] ? "From salary records" : "No salary table yet"; ?></small>
          </div>
        </div>
      </div>

      <ul class="nav nav-tabs report-tabs" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billingReport" type="button" role="tab">Billing</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expensesReport" type="button" role="tab">Expenses</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staffReport" type="button" role="tab">Staff List</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salaryReport" type="button" role="tab">Staff Salary</button>
        </li>
      </ul>

      <div class="tab-content report-tab-content">
        <div class="tab-pane fade show active" id="billingReport" role="tabpanel" aria-labelledby="billing-tab">
          <div class="report-section-heading">
            <div>
              <h6 class="mb-0">Billing Report</h6>
              <p class="text-muted small mb-0">Latest booking charges and delivery statuses.</p>
            </div>
            <div class="report-status-line">
              <span class="badge bg-warning-subtle text-warning"><?php echo (int) $summary["pendingCount"]; ?> pending</span>
              <span class="badge bg-primary-subtle text-primary"><?php echo (int) $summary["inTransitCount"]; ?> on transit</span>
              <span class="badge bg-success-subtle text-success"><?php echo (int) $summary["completedCount"]; ?> delivered</span>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle report-table">
              <thead>
                <tr>
                  <th>Booking</th>
                  <th>Customer</th>
                  <th>Trip</th>
                  <th>Pickup Date</th>
                  <th>Status</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($billingRows as $row): ?>
                  <tr>
                    <td>#<?php echo (int) $row["bookingID"]; ?></td>
                    <td>
                      <strong><?php echo reportText($row["customerName"], "Customer"); ?></strong>
                      <div class="small text-muted"><?php echo reportText(ucfirst($row["customerType"])); ?></div>
                    </td>
                    <td>Trip #<?php echo (int) $row["tripID"]; ?></td>
                    <td><?php echo htmlspecialchars(reportDate($row["pickupDateTime"])); ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary"><?php echo reportText($row["status"]); ?></span></td>
                    <td class="text-end fw-semibold"><?php echo reportMoney($row["price"]); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="expensesReport" role="tabpanel" aria-labelledby="expenses-tab">
          <div class="report-section-heading">
            <div>
              <h6 class="mb-0">Expenses Report</h6>
              <p class="text-muted small mb-0">Maintenance, fuel, supplies, and other business costs.</p>
            </div>
          </div>
          <?php if (empty($expenseRows)): ?>
            <div class="report-empty">No expense records found yet.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle report-table">
                <thead>
                  <tr>
                    <th>Record</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($expenseRows as $row): ?>
                    <tr>
                      <td>#<?php echo reportText($row["recordID"]); ?></td>
                      <td><?php echo htmlspecialchars(reportDate($row["recordDate"])); ?></td>
                      <td><?php echo reportText($row["category"]); ?></td>
                      <td><?php echo reportText($row["description"]); ?></td>
                      <td><span class="badge bg-secondary-subtle text-secondary"><?php echo reportText($row["status"]); ?></span></td>
                      <td class="text-end fw-semibold"><?php echo reportMoney($row["amount"]); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="staffReport" role="tabpanel" aria-labelledby="staff-tab">
          <div class="report-section-heading">
            <div>
              <h6 class="mb-0">Staff List</h6>
              <p class="text-muted small mb-0">Registered drivers, assistants, and admins.</p>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle report-table">
              <thead>
                <tr>
                  <th>Staff</th>
                  <th>Role</th>
                  <th>Contact</th>
                  <th>Status</th>
                  <th>Date Created</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($staffRows as $employee): ?>
                  <tr>
                    <td>
                      <strong><?php echo reportText(reportStaffName($employee), "Employee"); ?></strong>
                      <div class="small text-muted">ID #<?php echo (int) $employee["id"]; ?></div>
                    </td>
                    <td><span class="badge bg-primary-subtle text-primary"><?php echo reportText(ucfirst($employee["empType"])); ?></span></td>
                    <td>
                      <?php echo reportText($employee["empPhoneNumber"]); ?>
                      <div class="small text-muted"><?php echo reportText($employee["empEmail"]); ?></div>
                    </td>
                    <td>
                      <span class="badge <?php echo $employee["empStatus"] === "active" ? "bg-success-subtle text-success" : "bg-secondary-subtle text-secondary"; ?>">
                        <?php echo reportText(ucfirst($employee["empStatus"])); ?>
                      </span>
                    </td>
                    <td><?php echo htmlspecialchars(reportDate($employee["dateCreated"])); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="salaryReport" role="tabpanel" aria-labelledby="salary-tab">
          <div class="report-section-heading">
            <div>
              <h6 class="mb-0">Staff Salary Report</h6>
              <p class="text-muted small mb-0">Payroll and staff salary records.</p>
            </div>
          </div>
          <?php if (empty($salaryRows)): ?>
            <div class="report-empty">No staff salary records found yet.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle report-table">
                <thead>
                  <tr>
                    <th>Record</th>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($salaryRows as $row): ?>
                    <tr>
                      <td>#<?php echo reportText($row["recordID"]); ?></td>
                      <td><?php echo reportText($row["employeeName"]); ?></td>
                      <td><?php echo htmlspecialchars(reportDate($row["recordDate"])); ?></td>
                      <td><span class="badge bg-secondary-subtle text-secondary"><?php echo reportText($row["status"]); ?></span></td>
                      <td class="text-end fw-semibold"><?php echo reportMoney($row["amount"]); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .reports-page {
    max-width: 1440px;
    margin: 0 auto;
  }

  .reports-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
  }

  .report-stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 1rem;
    background: var(--bs-body-bg);
  }

  .report-stat-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 44px;
    border-radius: 0.5rem;
    font-size: 1.35rem;
  }

  .report-tabs {
    gap: 0.25rem;
  }

  .report-tab-content {
    border: 1px solid var(--bs-border-color);
    border-top: 0;
    padding: 1rem;
  }

  .report-section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .report-status-line {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .report-table {
    min-width: 780px;
  }

  .report-empty {
    border: 1px dashed var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 2rem;
    color: var(--bs-secondary-color);
    text-align: center;
    background: var(--bs-tertiary-bg);
  }

  @media (max-width: 1199.98px) {
    .reports-summary-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 575.98px) {
    .card-body {
      padding: 1rem !important;
    }

    .reports-summary-grid {
      grid-template-columns: 1fr;
    }

    .report-stat-card {
      align-items: flex-start;
    }

    .report-tab-content {
      padding: 0.75rem;
    }
  }
</style>
