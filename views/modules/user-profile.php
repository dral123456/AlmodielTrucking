<?php
$role = $_SESSION["role"] ?? '';
$id   = $_SESSION["id"]   ?? null;

// Normalize role
$isCustomerIndividual = $role === 'customer-individual';
$isCustomerCompany    = $role === 'customer-company';
$isDriver             = $role === 'driver';
$isAssistant          = $role === 'assistant';
$isAdmin              = $role === 'admin';

// Fetch data based on role
$user     = null;
$location = null;

if ($isCustomerIndividual || $isCustomerCompany) {
    require_once "models/customer.model.php";
    $user = ControllerCustomer::ctrGetCustomer($id);

    // Fetch location if locationID exists
    if (!empty($user["locationID"])) {
        $pdo  = (new Connection)->connect();
        $stmt = $pdo->prepare("SELECT * FROM location WHERE locationID = :locationID LIMIT 1");
        $stmt->bindValue(":locationID", (int) $user["locationID"], PDO::PARAM_INT);
        $stmt->execute();
        $location = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} elseif ($isDriver || $isAssistant || $isAdmin) {
    require_once "models/employee.model.php";
    $user = ControllerEmployee::ctrGetEmployee($id);
}

if (!$user) {
    echo '<div class="alert alert-danger">Unable to load profile.</div>';
    return;
}

// Build display name and details
if ($isCustomerIndividual) {
    $displayName = trim($user["customerFName"] . " " . $user["customerMI"] . " " . $user["customerLName"]);
    $subTitle     = "Individual Customer";
} elseif ($isCustomerCompany) {
    $displayName = $user["customerFName"]; // company name
    $subTitle     = "Company";
} elseif ($isDriver) {
    $displayName = trim($user["empFName"] . " " . $user["empMI"] . " " . $user["empLName"]);
    $subTitle     = "Driver";
} elseif ($isAssistant) {
    $displayName = trim($user["empFName"] . " " . $user["empMI"] . " " . $user["empLName"]);
    $subTitle     = "Assistant";
} elseif ($isAdmin) {
    $displayName = trim($user["empFName"] . " " . $user["empMI"] . " " . $user["empLName"]);
    $subTitle     = "Administrator";
}

// Build address string from location
$addressParts = [];
if ($location) {
    foreach (["street", "barangay", "city", "province"] as $part) {
        if (!empty($location[$part])) $addressParts[] = $location[$part];
    }
} elseif (!empty($user["province"])) {
    $addressParts[] = $user["province"];
}
$address = !empty($addressParts) ? implode(", ", $addressParts) : "—";
?>

<div class="pages-profile">

    <!-- Breadcrumb -->
    <div class="main-breadcrumb d-flex align-items-center my-3">
        <h2 class="breadcrumb-title mb-0 flex-grow-1 fs-14">Profile</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">Page</a></li>
                <li class="breadcrumb-item active">Profile</li>
            </ol>
        </nav>
    </div>

</div>

<!-- Background Header -->
<div class="main-profile-bg position-relative mb-4">
    <img src="views/assets/images/p-bg.jpg"
         class="w-100 rounded-3"
         style="height: 180px; object-fit: cover;">
</div>

<!-- PROFILE HEADER CARD -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">

        <div class="d-flex align-items-center gap-4 flex-wrap">

            <!-- Avatar -->
            <div class="position-relative">
                <img src="views/assets/images/avatar/avatar-3.jpg"
                     class="rounded-circle border"
                     width="90" height="90">

                <span class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white"
                      style="width:12px; height:12px;"></span>
            </div>

            <!-- Info -->
            <div class="flex-grow-1">
                <h4 class="mb-1"><?= htmlspecialchars($displayName) ?></h4>
                <div class="text-muted mb-1"><?= htmlspecialchars($subTitle) ?></div>
                <div class="text-muted small">
                    <i class="ri-map-pin-line"></i>
                    <?= htmlspecialchars($address) ?>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- PROFILE DETAILS -->
<div class="row g-4">

    <!-- LEFT CARD -->
    <div class="col-lg-4">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Account Summary</h5>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <small class="text-muted">Role</small>
                    <div class="fw-semibold"><?= ucfirst($role) ?></div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Full Name</small>
                    <div class="fw-semibold"><?= htmlspecialchars($displayName) ?></div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Status</small><br>
                    <span class="badge bg-success">Active</span>
                </div>

                <?php if ($isCustomerIndividual || $isCustomerCompany): ?>
                <div>
                    <small class="text-muted">Location</small>
                    <div class="fw-semibold"><?= htmlspecialchars($address) ?></div>
                </div>
                <?php endif; ?>

            </div>
        </div>

    </div>

    <!-- RIGHT CARD -->
    <div class="col-lg-8">

        <div class="card shadow-sm border-0">

            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Personal Information</h5>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <?php if ($isCustomerIndividual): ?>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Full Name</label>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($user["customerFName"] . " " . $user["customerMI"] . " " . $user["customerLName"]) ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Email</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["email"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Phone</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["phoneNumber"] ?? "—") ?></div>
                        </div>

                    <?php elseif ($isCustomerCompany): ?>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Company Name</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["customerFName"]) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Contact Person</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["contactPerson"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Email</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["email"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Phone</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["phoneNumber"] ?? "—") ?></div>
                        </div>

                    <?php elseif ($isDriver): ?>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Email</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["empEmail"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Phone</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["empPhoneNumber"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">License No</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["licenseNumber"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">License Expiry</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["licenseExpire"] ?? "—") ?></div>
                        </div>

                    <?php elseif ($isAssistant || $isAdmin): ?>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Email</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["empEmail"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Phone</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["empPhoneNumber"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Birth Date</label>
                            <div class="fw-semibold"><?= htmlspecialchars($user["empBirthDate"] ?? "—") ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Role</label>
                            <div class="fw-semibold"><?= ucfirst($role) ?></div>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>