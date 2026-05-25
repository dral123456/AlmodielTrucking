<?php
require_once "controllers/customer.controller.php";
require_once "models/customer.model.php";

$companies = ControllerCustomer::ctrCompanyList();
$companyMapData = array();

function manageCompanyText($value, $fallback = "") {
  if ($value === null || $value === "") {
    return $fallback;
  }

  return (string) $value;
}

function manageCompanyHtml($value, $fallback = "") {
  return htmlspecialchars(manageCompanyText($value, $fallback), ENT_QUOTES, "UTF-8");
}

foreach ($companies as $company) {
  if ($company["warehouseLatitude"] !== null && $company["warehouseLongitude"] !== null) {
    $companyName = trim(manageCompanyText($company["customerFName"] ?? ""));
    if ($companyName === "") {
      $companyName = manageCompanyText($company["contactPerson"] ?? "");
    }

    $companyMapData[] = array(
      "name" => $companyName,
      "contactPerson" => $company["contactPerson"],
      "latitude" => (float) $company["warehouseLatitude"],
      "longitude" => (float) $company["warehouseLongitude"],
      "address" => implode(", ", array_filter(array(
        manageCompanyText($company["houseNumber"] ?? ""),
        manageCompanyText($company["street"] ?? ""),
        manageCompanyText($company["barangay"] ?? ""),
        manageCompanyText($company["city"] ?? ""),
        manageCompanyText($company["province"] ?? "")
      )))
    );
  }
}
?>

<div class="manage-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Company Management</h5>
        <p class="text-muted small mb-0">View registered company customers and their warehouse details.</p>
      </div>
      <a href="customer-reg?type=company" class="btn btn-primary">
        <i class="ri-add-line me-1"></i> Add Company
      </a>
    </div>
    <div class="card-body p-4">
      <div class="manage-map-panel mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <h6 class="text-uppercase text-muted mb-1">
              <i class="ri-map-pin-2-line me-1"></i> Warehouse Map
            </h6>
            <p class="text-muted small mb-0">View pinned warehouses here. Use the pin button in a company row to change its warehouse pin.</p>
          </div>
          <span class="badge bg-primary-subtle text-primary"><?php echo count($companyMapData); ?> pinned</span>
        </div>
        <div id="companyManageMap"></div>
      </div>

      <div class="manage-toolbar mb-3">
        <div class="form-icon">
          <i class="ri-search-line text-muted"></i>
          <input type="text" class="form-control form-control-icon manage-search" data-target="#companyManageTable" placeholder="Search companies">
        </div>
        <select class="form-select manage-status-filter" data-target="#companyManageTable">
          <option value="all">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <div class="table-responsive">
        <table class="table align-middle manage-table" id="companyManageTable">
          <thead>
            <tr>
              <th>Company</th>
              <th>Contact</th>
              <th>Warehouse Address</th>
              <th>Coordinates</th>
              <th>Status</th>
              <th>Document</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($companies as $company): ?>
              <?php
                $companyName = trim(manageCompanyText($company["customerFName"] ?? ""));
                if ($companyName === "") {
                  $companyName = manageCompanyText($company["contactPerson"] ?? "");
                }
                $address = implode(", ", array_filter(array(
                  manageCompanyText($company["houseNumber"] ?? ""),
                  manageCompanyText($company["street"] ?? ""),
                  manageCompanyText($company["barangay"] ?? ""),
                  manageCompanyText($company["city"] ?? ""),
                  manageCompanyText($company["province"] ?? "")
                )));
                $coords = ($company["warehouseLatitude"] !== null && $company["warehouseLongitude"] !== null)
                  ? $company["warehouseLatitude"] . ", " . $company["warehouseLongitude"]
                  : "Not pinned";
              ?>
              <tr
                data-status="<?php echo manageCompanyHtml($company["status"] ?? ""); ?>"
                data-id="<?php echo manageCompanyHtml($company["id"] ?? ""); ?>"
                data-entity="company"
                data-company-name="<?php echo manageCompanyHtml($companyName); ?>"
                data-contact-person="<?php echo manageCompanyHtml($company["contactPerson"] ?? ""); ?>"
                data-email="<?php echo manageCompanyHtml($company["email"] ?? ""); ?>"
                data-phone-number="<?php echo manageCompanyHtml($company["phoneNumber"] ?? ""); ?>"
                data-province="<?php echo manageCompanyHtml($company["province"] ?? ""); ?>"
                data-city="<?php echo manageCompanyHtml($company["city"] ?? ""); ?>"
                data-barangay="<?php echo manageCompanyHtml($company["barangay"] ?? ""); ?>"
                data-street="<?php echo manageCompanyHtml($company["street"] ?? ""); ?>"
                data-house-number="<?php echo manageCompanyHtml($company["houseNumber"] ?? ""); ?>"
                data-warehouse-latitude="<?php echo manageCompanyHtml($company["warehouseLatitude"] ?? ""); ?>"
                data-warehouse-longitude="<?php echo manageCompanyHtml($company["warehouseLongitude"] ?? ""); ?>"
              >
                <td>
                  <strong><?php echo manageCompanyHtml($companyName); ?></strong>
                  <div class="small text-muted">Registered <?php echo manageCompanyHtml($company["dateRegistered"] ?? ""); ?></div>
                </td>
                <td>
                  <?php echo manageCompanyHtml($company["contactPerson"] ?? "", "-"); ?>
                  <div class="small text-muted"><?php echo manageCompanyHtml($company["email"] ?? ""); ?></div>
                  <div class="small text-muted"><?php echo manageCompanyHtml($company["phoneNumber"] ?? ""); ?></div>
                </td>
                <td><?php echo manageCompanyHtml($address, "-"); ?></td>
                <td><?php echo manageCompanyHtml($coords); ?></td>
                <td>
                  <span class="badge <?php echo $company["status"] === "active" ? "bg-success-subtle text-success" : "bg-secondary-subtle text-secondary"; ?>">
                    <?php echo manageCompanyHtml(ucfirst(manageCompanyText($company["status"] ?? ""))); ?>
                  </span>
                </td>
                <td>
                  <?php if (!empty($company["companyDocument"])): ?>
                    <a class="btn btn-sm btn-light" href="uploads/<?php echo manageCompanyHtml($company["companyDocument"]); ?>" target="_blank">
                      <i class="ri-file-line me-1"></i> View
                    </a>
                  <?php else: ?>
                    <span class="text-muted">None</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light manage-edit" title="Edit company details and warehouse pin"><i class="ri-edit-line"></i></button>
                    <button type="button" class="btn btn-light text-danger manage-archive" title="Archive company"><i class="ri-archive-line"></i></button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="manage-empty text-center text-muted border rounded p-4 d-none">No companies found.</div>
    </div>
  </div>
</div>

<script>
  window.companyWarehouseMapData = <?php echo json_encode($companyMapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<?php include __DIR__ . "/manage-style.php"; ?>
