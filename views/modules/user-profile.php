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
    <div class="main-breadcrumb d-flex align-items-center my-3 position-relative">
        <h2 class="breadcrumb-title mb-0 flex-grow-1 fs-14">Profile</h2>
        <div class="flex-shrink-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-end mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Page</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profile</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="main-profile-bg position-relative">
    <div class="profile-bg">
        <img src="views/assets/images/p-bg.jpg" alt="Profile Background" class="w-100 h-100 object-fit-cover">
    </div>
</div>

<div class="position-relative z-1 text-end edit-btn">
    <button class="btn border border-white text-white">Edit Profile</button>
</div>

<!-- Profile Header Card -->
<div class="card overflow-hidden position-relative z-1">
    <div class="card-body p-5">
        <div class="d-flex justify-content-between flex-wrap align-items-center gap-6">
            <div class="flex-shrink-0">
                <div class="position-relative d-inline-block">
                    <img src="views/assets/images/avatar/avatar-3.jpg" alt="Avatar Image" class="h-100px w-100px rounded-pill">
                    <div class="h-30px w-30px rounded-pill bg-primary d-flex justify-content-center align-items-center text-white border border-3 border-light-subtle position-absolute fs-12 bottom-0 end-0">
                        <i class="bi bi-camera"></i>
                    </div>
                    <span class="position-absolute profile-dot bg-success rounded-circle">
                        <span class="visually-hidden">Online</span>
                    </span>
                </div>
            </div>
            <div class="flex-grow-1">
                <h4 class="mb-1"><?= htmlspecialchars($displayName) ?></h4>
                <p class="text-muted mb-1"><?= htmlspecialchars($subTitle) ?></p>
                <p class="text-muted mb-0"><?= htmlspecialchars($address) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Profile Details -->
<div class="row">
    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Personal Details</h5>
            </div>
            <div class="card-body d-flex flex-column gap-4">

                <?php if($isCustomerIndividual || $isCustomerCompany):?>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-map-pin-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($address) ?></p>
                    </div>
                <?php endif; ?>
                

                <?php if ($isCustomerIndividual): ?>
                    <!-- Individual Customer -->
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-user-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["customerFName"] . " " . $user["customerMI"] . " " . $user["customerLName"]) ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-mail-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["email"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-phone-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["phoneNumber"] ?? "—") ?></p>
                    </div>

                <?php elseif ($isCustomerCompany): ?>
                    <!-- Company Customer -->
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-building-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["customerFName"]) ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-user-line fs-16 text-muted"></i>
                        <p class="mb-0">Contact: <?= htmlspecialchars($user["contactPerson"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-mail-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["email"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-phone-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["phoneNumber"] ?? "—") ?></p>
                    </div>

                <?php elseif ($isDriver): ?>
                    <!-- Driver -->
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-mail-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["empEmail"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-phone-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["empPhoneNumber"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-calendar-line fs-16 text-muted"></i>
                        <p class="mb-0">Born: <?= htmlspecialchars($user["empBirthDate"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-drive-line fs-16 text-muted"></i>
                        <p class="mb-0">License No: <?= htmlspecialchars($user["licenseNumber"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-calendar-check-line fs-16 text-muted"></i>
                        <p class="mb-0">License Expires: <?= htmlspecialchars($user["licenseExpire"] ?? "—") ?></p>
                    </div>
                    <?php if (!empty($user["licenseImage"])): ?>
                    <div class="d-flex align-items-start gap-3">
                        <i class="ri-image-line fs-16 text-muted mt-1"></i>
                        <div>
                            <p class="mb-1 text-muted">License Image</p>
                            <img src="uploads/<?= htmlspecialchars($user["licenseImage"]) ?>"
                                 alt="License Image"
                                 class="img-fluid rounded"
                                 style="max-width: 200px;">
                        </div>
                    </div>
                    <?php endif; ?>

                <?php elseif ($isAssistant || $isAdmin): ?>
                    <!-- Assistant / Admin -->
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-mail-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["empEmail"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-phone-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars($user["empPhoneNumber"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-calendar-line fs-16 text-muted"></i>
                        <p class="mb-0">Born: <?= htmlspecialchars($user["empBirthDate"] ?? "—") ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="ri-shield-user-line fs-16 text-muted"></i>
                        <p class="mb-0"><?= htmlspecialchars(ucfirst($role)) ?></p>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>