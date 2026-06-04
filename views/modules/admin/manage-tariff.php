<?php
require_once "controllers/tariff.controller.php";
require_once "models/tariff.model.php";

$companies = ControllerTariff::ctrCompanyList();
$selectedCustomerID = isset($_GET["customerID"]) ? (int) $_GET["customerID"] : 0;
$tariffs = ControllerTariff::ctrTariffRows();

function tariffMoney($value) {
  return "PHP " . number_format((float) $value, 2);
}
?>

<div class="manage-page tariff-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Tariff Management</h5>
        <p class="text-muted small mb-0">Manage company-specific route rates by truck type and fuel subsidy rule.</p>
      </div>
      <button type="button" class="btn btn-primary" id="tariffAddBtn">
        <i class="ri-add-line me-1"></i> Add Tariff
      </button>
    </div>

    <div class="card-body p-4">
      <form class="tariff-toolbar mb-4" id="tariffFilterForm">
        <div>
          <label class="form-label">Company</label>
          <select class="form-select" id="tariffCompanyFilter" name="customerID">
            <option value="">All companies</option>
            <?php foreach ($companies as $company): ?>
              <option value="<?php echo (int) $company["id"]; ?>" <?php echo $selectedCustomerID === (int) $company["id"] ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($company["companyName"]); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Search</label>
          <div class="form-icon">
            <i class="ri-search-line text-muted"></i>
            <input type="text" class="form-control form-control-icon" id="tariffSearch" placeholder="Search destination or truck">
          </div>
        </div>
        <div class="tariff-toolbar-actions">
          <button type="submit" class="btn btn-primary">
            <i class="ri-filter-3-line me-1"></i> Apply
          </button>
          <button type="button" class="btn btn-light" id="tariffClearFilters">
            <i class="ri-refresh-line me-1"></i> Clear
          </button>
        </div>
      </form>

      <section class="tariff-import-panel mb-4">
        <div>
          <h6 class="mb-1">CSV Import</h6>
          <p class="text-muted small mb-0">Required columns: Origin, Destination, Distance, Current Rate. Select company and truck type before importing.</p>
        </div>
        <form id="tariffImportForm" class="tariff-import-grid" enctype="multipart/form-data">
          <select class="form-select" name="customerID" required>
            <option value="">Select company</option>
            <?php foreach ($companies as $company): ?>
              <option value="<?php echo (int) $company["id"]; ?>" <?php echo $selectedCustomerID === (int) $company["id"] ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($company["companyName"]); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="text" class="form-control" name="truckType" placeholder="Truck type e.g. 6W" required>
          <input type="text" class="form-control" name="branch" value="BACOLOD" placeholder="Branch">
          <input type="text" class="form-control" name="origin" value="BACOLOD" placeholder="Origin">
          <input type="number" class="form-control" name="fuelRangeStart" value="60" step="0.01" placeholder="Fuel start">
          <input type="number" class="form-control" name="fuelRangeEnd" value="65" step="0.01" placeholder="Fuel end">
          <label class="form-check tariff-check">
            <input class="form-check-input" type="checkbox" name="hasFuelSubsidy" value="1" checked>
            <span class="form-check-label">Fuel subsidy</span>
          </label>
          <input type="file" class="form-control" name="tariffCsv" accept=".csv,text/csv" required>
          <button type="submit" class="btn btn-success">
            <i class="ri-file-upload-line me-1"></i> Import CSV
          </button>
        </form>
      </section>

      <section class="tariff-import-panel mb-4">
        <div>
          <h6 class="mb-1">Bulk Fuel Rule</h6>
          <p class="text-muted small mb-0">Change the base fuel range for a company. Add truck type to limit the update to that truck only.</p>
        </div>
        <form id="tariffBulkFuelForm" class="tariff-import-grid">
          <select class="form-select" name="customerID" required>
            <option value="">Select company</option>
            <?php foreach ($companies as $company): ?>
              <option value="<?php echo (int) $company["id"]; ?>" <?php echo $selectedCustomerID === (int) $company["id"] ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($company["companyName"]); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="text" class="form-control" name="truckType" placeholder="Truck type, blank = all">
          <input type="number" class="form-control" name="fuelRangeStart" value="60" step="0.01" placeholder="Fuel start" required>
          <input type="number" class="form-control" name="fuelRangeEnd" value="65" step="0.01" placeholder="Fuel end" required>
          <label class="form-check tariff-check">
            <input class="form-check-input" type="checkbox" name="hasFuelSubsidy" value="1" checked>
            <span class="form-check-label">Fuel subsidy</span>
          </label>
          <button type="submit" class="btn btn-primary">
            <i class="ri-price-tag-3-line me-1"></i> Apply Fuel Rule
          </button>
        </form>
      </section>

      <div class="table-responsive">
        <table class="table align-middle manage-table" id="tariffTable">
          <thead>
            <tr>
              <th>Company</th>
              <th>Route</th>
              <th>Truck</th>
              <th>Fuel Rule</th>
              <th class="text-end">Base Rate</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tariffs as $tariff): ?>
              <tr
                data-tariff-id="<?php echo (int) $tariff["tariffID"]; ?>"
                data-customer-id="<?php echo htmlspecialchars($tariff["customerID"]); ?>"
                data-company="<?php echo htmlspecialchars($tariff["companyName"]); ?>"
                data-branch="<?php echo htmlspecialchars($tariff["branch"]); ?>"
                data-origin="<?php echo htmlspecialchars($tariff["origin"]); ?>"
                data-destination="<?php echo htmlspecialchars($tariff["destination"]); ?>"
                data-distance-km="<?php echo htmlspecialchars($tariff["distanceKm"]); ?>"
                data-truck-type="<?php echo htmlspecialchars($tariff["truckType"]); ?>"
                data-base-rate="<?php echo htmlspecialchars($tariff["baseRate"]); ?>"
                data-fuel-range-start="<?php echo htmlspecialchars($tariff["fuelRangeStart"]); ?>"
                data-fuel-range-end="<?php echo htmlspecialchars($tariff["fuelRangeEnd"]); ?>"
                data-has-fuel-subsidy="<?php echo (int) ($tariff["hasFuelSubsidy"] ?? 1); ?>"
                data-fuel-subsidy="<?php echo htmlspecialchars($tariff["fuelSubsidy"]); ?>"
                data-status="<?php echo htmlspecialchars($tariff["status"]); ?>"
              >
                <td>
                  <strong><?php echo htmlspecialchars($tariff["companyName"]); ?></strong>
                  <div class="small text-muted">ID #<?php echo htmlspecialchars($tariff["customerID"] ?: "Default"); ?></div>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($tariff["origin"]); ?> to <?php echo htmlspecialchars($tariff["destination"]); ?></strong>
                  <div class="small text-muted"><?php echo number_format((float) $tariff["distanceKm"], 2); ?> km | <?php echo htmlspecialchars($tariff["branch"]); ?></div>
                </td>
                <td><?php echo htmlspecialchars($tariff["truckType"]); ?></td>
                <td>
                  <?php if ((int) ($tariff["hasFuelSubsidy"] ?? 1) === 1): ?>
                    <span class="badge bg-success-subtle text-success">With subsidy</span>
                    <div class="small text-muted"><?php echo htmlspecialchars($tariff["fuelRangeStart"]); ?>-<?php echo htmlspecialchars($tariff["fuelRangeEnd"]); ?> base</div>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary">No subsidy</span>
                  <?php endif; ?>
                </td>
                <td class="text-end fw-semibold"><?php echo tariffMoney($tariff["baseRate"]); ?></td>
                <td>
                  <span class="badge <?php echo $tariff["status"] === "active" ? "bg-success-subtle text-success" : "bg-secondary-subtle text-secondary"; ?>">
                    <?php echo htmlspecialchars(ucfirst($tariff["status"])); ?>
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light tariff-edit"><i class="ri-edit-line"></i></button>
                    <button type="button" class="btn btn-light text-danger tariff-archive"><i class="ri-archive-line"></i></button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="tariff-empty text-center text-muted border rounded p-4 <?php echo empty($tariffs) ? "" : "d-none"; ?>">No tariff records found.</div>
    </div>
  </div>
</div>

<script>
  window.tariffCompanies = <?php echo json_encode($companies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<?php include __DIR__ . "/manage-style.php"; ?>
