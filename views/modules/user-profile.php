<?php
$role = $_SESSION["role"] ?? '';
$id   = $_SESSION["id"] ?? null;

// Roles
$isCustomerIndividual = $role === 'customer-individual';
$isCustomerCompany    = $role === 'customer-company';
$isDriver             = $role === 'driver';
$isAssistant          = $role === 'assistant';
$isAdmin              = $role === 'admin';

// Fetch data
$user = null;
$location = null;

if ($isCustomerIndividual || $isCustomerCompany) {

    require_once "models/customer.model.php";
    $user = ControllerCustomer::ctrGetCustomer($id);

    if (!empty($user["locationID"])) {
        $pdo = (new Connection)->connect();
        $stmt = $pdo->prepare("SELECT * FROM location WHERE locationID = :locationID LIMIT 1");
        $stmt->bindValue(":locationID", (int)$user["locationID"], PDO::PARAM_INT);
        $stmt->execute();
        $location = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} else {

    require_once "models/employee.model.php";
    $user = ControllerEmployee::ctrGetEmployee($id);
}

if (!$user) {
    echo "<div class='alert alert-danger'>Unable to load profile</div>";
    return;
}

// Name
$displayName = ($isCustomerIndividual || $isCustomerCompany)
    ? trim($user["customerFName"] . " " . ($user["customerMI"] ?? '') . " " . $user["customerLName"])
    : trim($user["empFName"] . " " . $user["empMI"] . " " . $user["empLName"]);

$subTitle = ucfirst($role);

// Address
$address = "—";
if ($location) {
    $address = implode(", ", array_filter([
        $location["street"] ?? null,
        $location["barangay"] ?? null,
        $location["city"] ?? null,
        $location["province"] ?? null
    ]));
}
?>

<!-- ===================== THEME STYLE ===================== -->
<style>

/* LIGHT MODE VARIABLES */
:root {
    --card-bg: #ffffff;
    --card-header: #f8f9fa;
    --text: #212529;
    --muted: #6c757d;
}

/* LIGHT MODE CARDS */
body .card {
    background: var(--card-bg) !important;
    color: var(--text) !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
}

body .card-header {
    background: var(--card-header) !important;
    color: var(--text) !important;
}

body .text-muted {
    color: var(--muted) !important;
}

/* DARK MODE VARIABLES */
body.dark-mode {
    --card-bg: #1b1b28;
    --card-header: #242436;
    --text: #e9ecef;
    --muted: #a9b0bb;
}

/* DARK MODE CARDS */
body.dark-mode .card {
    background: var(--card-bg) !important;
    color: var(--text) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
}

body.dark-mode .card-header {
    background: var(--card-header) !important;
    color: var(--text) !important;
}

body.dark-mode .bg-white {
    background: var(--card-header) !important;
}

body.dark-mode .text-muted {
    color: var(--muted) !important;
}

</style>

<!-- ===================== AUTO THEME FIX ===================== -->
<script>

(function () {

    const theme = localStorage.getItem("theme");

    // 🔥 IMPORTANT: ALWAYS RESET FIRST
    document.body.classList.remove("dark-mode");
    document.documentElement.classList.remove("dark-mode");

    // APPLY DARK ONLY IF SAVED
    if (theme === "dark") {
        document.body.classList.add("dark-mode");
        document.documentElement.classList.add("dark-mode");
    }

})();

// THEME TOGGLE FUNCTION
window.toggleTheme = function(mode) {
    if (mode === "light") {
        // REMOVE dark-mode class
        document.body.classList.remove("dark-mode");
        document.documentElement.classList.remove("dark-mode");
        localStorage.setItem("theme", "light");

        // Update button active states
        document.getElementById("lightModeBtn").classList.add("active");
        document.getElementById("darkModeBtn").classList.remove("active");
    } else if (mode === "dark") {
        // ADD dark-mode class
        document.body.classList.add("dark-mode");
        document.documentElement.classList.add("dark-mode");
        localStorage.setItem("theme", "dark");

        // Update button active states
        document.getElementById("lightModeBtn").classList.remove("active");
        document.getElementById("darkModeBtn").classList.add("active");
    }
};

// ATTACH CLICK LISTENERS TO BUTTONS
document.addEventListener("DOMContentLoaded", function() {
    const lightModeBtn = document.getElementById("lightModeBtn");
    const darkModeBtn = document.getElementById("darkModeBtn");

    if (lightModeBtn) {
        lightModeBtn.addEventListener("click", function(e) {
            e.preventDefault();
            toggleTheme("light");
        });
    }

    if (darkModeBtn) {
        darkModeBtn.addEventListener("click", function(e) {
            e.preventDefault();
            toggleTheme("dark");
        });
    }

    // Set initial button state based on current theme
    const currentTheme = localStorage.getItem("theme") || "light";
    if (currentTheme === "dark") {
        if (lightModeBtn) lightModeBtn.classList.remove("active");
        if (darkModeBtn) darkModeBtn.classList.add("active");
    } else {
        if (lightModeBtn) lightModeBtn.classList.add("active");
        if (darkModeBtn) darkModeBtn.classList.remove("active");
    }
});

</script>

<!-- ===================== HEADER ===================== -->
<div class="main-profile-bg position-relative mb-4">

    <img src="views/assets/images/background.avif"
         class="w-100 rounded-3"
         style="height: 180px; object-fit: cover; object-position: center 60%;">

    <div class="position-absolute top-50 start-50 translate-middle text-center">
        <h1 style="color:#0d6efd; font-size:42px; font-weight:bold;">
            Almodiel Trucking
        </h1>
    </div>

</div>

<!-- PROFILE CARD -->
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex gap-3 align-items-center">

        <img src="views/assets/images/avatar/avatar-3.jpg"
             width="90" height="90"
             class="rounded-circle border">

        <div>
            <h4 class="mb-1"><?= htmlspecialchars($displayName) ?></h4>
            <div class="text-muted"><?= htmlspecialchars($subTitle) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($address) ?></div>
        </div>

    </div>
</div>

<!-- DETAILS -->
<div class="row g-4">

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Account Summary</div>
            <div class="card-body">
                <p><b>Role:</b> <?= ucfirst($role) ?></p>
                <p><b>Name:</b> <?= htmlspecialchars($displayName) ?></p>
                <p><b>Status:</b> <span class="badge bg-success">Active</span></p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Personal Information</div>
            <div class="card-body">

                <?php if ($isCustomerIndividual): ?>
                    <p>Email: <?= htmlspecialchars($user["email"] ?? "—") ?></p>
                    <p>Phone: <?= htmlspecialchars($user["phoneNumber"] ?? "—") ?></p>

                <?php elseif ($isDriver || $isAssistant || $isAdmin): ?>
                    <p>Email: <?= htmlspecialchars($user["empEmail"] ?? "—") ?></p>
                    <p>Phone: <?= htmlspecialchars($user["empPhoneNumber"] ?? "—") ?></p>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>