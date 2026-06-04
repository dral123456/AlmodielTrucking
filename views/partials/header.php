<?php
if(isset($_SESSION['role'])) {
    date_default_timezone_set("Asia/Manila");
    $role = $_SESSION['role'];
    if($role === 'customer-individual' || $role === 'customer-company') {
        require_once 'controllers/customer.controller.php';
        require_once 'models/customer.model.php';

        $customer = ControllerCustomer::ctrGetCustomer($_SESSION['id']);

        $customerType = $customer['customerType'];
        $fName = $customer['customerFName'];
        if(strtolower($customerType) === 'company') {
            $fullname = $fName;
        } else {
            $mI = $customer['customerMI'];
            $lName = $customer['customerLName'];
            $fullName = $fName . ' ' . $mI . ' ' . $lName;
        }
        $email = $customer['email'];
    } else {
        require_once 'controllers/employee.controller.php';
        require_once 'models/employee.model.php';

        $employee = ControllerEmployee::ctrGetEmployee($_SESSION['id']);

        $fName = $employee['empFName'];
        $mI = $employee['empMI'];
        $lName = $employee['empLName'];
        $fullName = $fName . ' ' . $mI . ' ' . $lName;
        $email = $employee['empEmail'];
    }
}

function headerNotificationText($value, $fallback = "-") {
    $value = trim((string) $value);
    return htmlspecialchars($value !== "" ? $value : $fallback);
}

function headerNotificationDate($value) {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date("M d, Y h:i A", $timestamp) : "Recently";
}

function headerNotificationCustomer($trip) {
    $customers = $trip["customers"] ?? array();
    if (empty($customers)) {
        return "Customer";
    }

    return implode(", ", array_slice($customers, 0, 2));
}

$adminNotifications = array();
$adminNotificationCount = 0;

if (($role ?? "") === "admin") {
    require_once "controllers/booking.controller.php";
    require_once "models/booking.model.php";
    require_once "controllers/incident.controller.php";
    require_once "models/incident.model.php";

    $notificationTrips = ControllerBooking::ctrTripOverviewList(0, "admin");
    $notificationIncidents = ControllerIncident::ctrIncidentList();
    $now = strtotime(date("Y-m-d H:i:s"));
    $upcomingLimit = strtotime("+7 days", $now);
    $upcomingTrips = array();
    $completedTrips = array();
    $activeIncidents = array();

    foreach ($notificationTrips as $trip) {
        $status = strtolower((string) ($trip["status"] ?? ""));
        $pickupTimestamp = strtotime((string) ($trip["firstPickupDateTime"] ?? ""));

        if ($pickupTimestamp && $pickupTimestamp >= $now && $pickupTimestamp <= $upcomingLimit && in_array($status, array("pending", "in-transit", "stopover"), true)) {
            $upcomingTrips[] = $trip;
        }

        if ($status === "completed") {
            $completedTrips[] = $trip;
        }
    }

    usort($upcomingTrips, function ($a, $b) {
        return strtotime($a["firstPickupDateTime"] ?? "") <=> strtotime($b["firstPickupDateTime"] ?? "");
    });

    usort($completedTrips, function ($a, $b) {
        return strtotime($b["firstPickupDateTime"] ?? "") <=> strtotime($a["firstPickupDateTime"] ?? "");
    });

    foreach ($notificationIncidents as $incident) {
        $incidentStatus = strtolower((string) ($incident["status"] ?? ""));
        if (in_array($incidentStatus, array("open", "reviewing"), true)) {
            $activeIncidents[] = $incident;
        }
    }

    $adminNotificationCount = count($upcomingTrips) + count(array_slice($completedTrips, 0, 3)) + count($activeIncidents);

    foreach (array_slice($activeIncidents, 0, 4) as $incident) {
        $adminNotifications[] = array(
            "key" => "incident-" . (int) ($incident["incidentID"] ?? 0) . "-" . ($incident["dateSubmitted"] ?? ""),
            "icon" => "ri-alarm-warning-line",
            "class" => "danger",
            "title" => "Incident report #" . (int) ($incident["incidentID"] ?? 0),
            "time" => headerNotificationDate($incident["dateSubmitted"] ?? ""),
            "message" => "Trip #" . (int) ($incident["tripID"] ?? 0) . " by " . ($incident["driverName"] ?? "Driver") . " needs admin review.",
            "link" => "incident-reports"
        );
    }

    foreach (array_slice($upcomingTrips, 0, 4) as $trip) {
        $adminNotifications[] = array(
            "key" => "upcoming-trip-" . (int) ($trip["tripID"] ?? 0) . "-" . ($trip["firstPickupDateTime"] ?? "") . "-" . ($trip["status"] ?? ""),
            "icon" => "ri-calendar-schedule-line",
            "class" => "primary",
            "title" => "Upcoming Trip #" . (int) ($trip["tripID"] ?? 0),
            "time" => headerNotificationDate($trip["firstPickupDateTime"] ?? ""),
            "message" => headerNotificationCustomer($trip) . " has " . (int) ($trip["bookingCount"] ?? 0) . " booking(s) scheduled.",
            "link" => "trips"
        );
    }

    foreach (array_slice($completedTrips, 0, 3) as $trip) {
        $adminNotifications[] = array(
            "key" => "completed-trip-" . (int) ($trip["tripID"] ?? 0) . "-" . ($trip["firstPickupDateTime"] ?? ""),
            "icon" => "ri-check-double-line",
            "class" => "success",
            "title" => "Delivery completed: Trip #" . (int) ($trip["tripID"] ?? 0),
            "time" => headerNotificationDate($trip["firstPickupDateTime"] ?? ""),
            "message" => headerNotificationCustomer($trip) . " delivery is marked completed.",
            "link" => "trips"
        );
    }
}

?>

<style>
    .admin-notification-count {
        position: absolute;
        top: 2px;
        right: 2px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 800;
        line-height: 1;
        border: 2px solid var(--bs-body-bg);
    }

    .admin-noti-item .avatar-md {
        flex: 0 0 auto;
    }
</style>

<!-- Begin Header -->
<header class="app-header" id="appHeader">
    <div class="container-fluid w-100">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-inline-flex align-items-center gap-2">
                <a href="sample" class="align-items-end logo-main d-none me-5">
                    <img height="35" width="34" class="logo-dark" alt="Dark Logo" src="views/assets/images/logo-md.png">
                    <h3 class="text-body-emphasis fw-bolder mb-0 ms-1 fs-6 lh-sm">Almodiel Trucking Services</h3>
                </a>
                <button type="button" class="vertical-toggle btn header-btn" id="toggleSidebar" aria-label="Toggle Sidebar">
                    <i class="bi bi-arrow-bar-left header-icon"></i>
                </button>
                <button type="button" class="horizontal-toggle btn header-btn d-none" id="toggleHorizontal" aria-label="Toggle Menu">
                    <i class="ri-menu-2-line header-icon"></i>
                </button>
                <!-- Search Bar -->
                <div class="form-icon right d-none d-md-block" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <input type="text" class="form-control form-control-icon bg-transparent rounded-pill min-w-300px" id="Search" placeholder="Search" required>
                    <div class="search-btn">
                        <div><i class="ri-search-line text-muted fs-16"></i></div>
                        <div><span class="badge bg-light-subtle text-muted">CTRL D</span></div>
                    </div>
                </div>
            </div>
            <div class="flex-shrink-0 d-flex align-items-center gap-4">
                <div class="d-flex gap-2 align-items-center">
                    <div class="dropdown pe-dropdown-mega d-none d-md-block">
                        <button
                            class="btn header-btn position-relative"
                            type="button"
                            id="adminNotificationButton"
                            data-admin-id="<?php echo (int) ($_SESSION["id"] ?? 0); ?>"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Notifications"
                        >
                            <i class="bi bi-bell"></i>
                            <?php if (($role ?? "") === "admin" && $adminNotificationCount > 0): ?>
                                <div class="icon-dot admin-notification-dot"></div>
                                <span class="admin-notification-count"><?php echo (int) min($adminNotificationCount, 99); ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-mega-md header-dropdown-menu pe-noti-dropdown-menu p-0">
                            <div class="p-3 border-bottom">
                                <h6 class="d-flex align-items-center mb-0">
                                    Notification
                                    <?php if (($role ?? "") === "admin"): ?>
                                        <span class="badge bg-primary-subtle text-primary ms-auto admin-notification-summary"><?php echo (int) $adminNotificationCount; ?> new</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto">No alerts</span>
                                    <?php endif; ?>
                                </h6>
                            </div>
                            <div>
                                <?php if (($role ?? "") === "admin" && !empty($adminNotifications)): ?>
                                    <?php foreach (array_slice($adminNotifications, 0, 10) as $notification): ?>
                                        <div class="noti-item admin-noti-item" data-notification-key="<?php echo headerNotificationText($notification["key"] ?? ""); ?>">
                                            <div class="avatar-md d-flex align-items-center justify-content-center bg-<?php echo headerNotificationText($notification["class"]); ?>-subtle text-<?php echo headerNotificationText($notification["class"]); ?> fs-16">
                                                <i class="<?php echo headerNotificationText($notification["icon"]); ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="<?php echo headerNotificationText($notification["link"]); ?>" class="text-decoration-none stretched-link">
                                                    <h6 class="mb-1 fw-semibold"><?php echo headerNotificationText($notification["title"]); ?></h6>
                                                </a>
                                                <p class="text-muted mb-2 fs-12"><?php echo headerNotificationText($notification["time"]); ?></p>
                                                <div class="p-2 bg-body-tertiary bg-opacity-50 rounded">
                                                    <p class="mb-0 lh-base fs-13"><?php echo headerNotificationText($notification["message"]); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="admin-notification-empty text-center p-4">
                                        <i class="ri-notification-off-line d-block fs-2 text-muted mb-2"></i>
                                        <h6 class="mb-1">No notifications</h6>
                                        <p class="text-muted mb-0 fs-13">Upcoming trips, completed deliveries, and incident reports will appear here.</p>
                                    </div>
                                <?php endif; ?>
                                <?php if (false): ?>
                                <div class="noti-item">
                                    <div class="avatar-md d-flex align-items-center justify-content-center bg-success-subtle text-success fs-16">
                                        <i class="bi bi-bag-check-fill"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <a href="#!" class="text-decoration-none stretched-link">
                                            <h6 class="mb-1 fw-semibold">Item Back in Stock</h6>
                                        </a>
                                        <p class="text-muted mb-2 fs-12 mb-2">Today, 02:45 PM</p>
                                        <div class="p-2 bg-body-tertiary bg-opacity-50 rounded">
                                            <p class="mb-0 lh-base fs-13">Good news! The item you wanted is back in stock. Grab it before it’s gone again!</p>
                                        </div>
                                    </div>
                                    <a href="#!" class="position-absolute top-0 end-0 mt-2 me-3 fs-18 link link-danger z-1">
                                        <i class="bi bi-x"></i>
                                    </a>
                                </div>
                                <div class="noti-item">
                                    <img src="views/assets/images/avatar/avatar-8.jpg" alt="Avatar Iamge" class="avatar-md">
                                    <div>
                                        <a href="#!" class="stretched-link">
                                            <h6 class="mb-1 text-muted"><strong class="fw-semibold text-body">Donald</strong><i class="ri-heart-3-fill text-danger ms-1"></i></h6>
                                        </a>
                                        <p class="text-muted mb-0 fs-12 mb-2">Friday, 11:29 PM</p>
                                    </div>
                                    <a href="#!" class="position-absolute top-10 end-0 fs-18 z-1 link link-danger me-3"><i class="bi bi-x"></i></a>
                                </div>
                                <div class="noti-item">
                                    <div class="avatar-md d-flex align-items-center justify-content-center bg-danger-subtle text-danger fs-16">
                                        <i class="bi bi-fire"></i>
                                    </div>
                                    <div>
                                        <a href="#!" class="stretched-link">
                                            <h6 class="mb-2">Birthday Reminder</h6>
                                        </a>
                                        <p class="text-muted mb-2 fs-12 mb-2">Tuesday, 02:45 PM</p>
                                        <div class="p-2 bg-body-tertiary bg-opacity-50 rounded">
                                            <p class="mb-0 lh-base fs-13">Don’t forget! It’s Emily birthday tomorrow. Send them a message!</p>
                                        </div>
                                    </div>
                                    <a href="#!" class="position-absolute top-10 end-0 fs-18 z-1 link link-danger me-3"><i class="bi bi-x"></i></a>
                                </div>
                                <div class="noti-item">
                                    <img src="views/assets/images/avatar/avatar-5.jpg" alt="Avatar Image" class="avatar-md">
                                    <div>
                                        <a href="#!" class="stretched-link">
                                            <h6 class="mb-1 text-muted"><strong class="fw-semibold text-body">Richard</strong><i class="bi bi-person-plus-fill text-primary fs-16 ms-1"></i></h6>
                                        </a>
                                        <p class="text-muted mb-0 fs-12">Monday, 07:14 AM</p>
                                    </div>
                                    <a href="#!" class="position-absolute top-10 end-0 fs-18 z-1 link link-danger me-3"><i class="bi bi-x"></i></a>
                                </div>
                                <div class="noti-item">
                                    <img src="views/assets/images/avatar/avatar-4.jpg" alt="Avatar Image" class="avatar-md">
                                    <div>
                                        <a href="#!" class="stretched-link">
                                            <h6 class="mb-2">Olivia <strong class="fw-normal text-muted fs-13">liked your recent post</strong></h6>
                                        </a>
                                        <p class="text-muted mb-0 fs-12">Thursday 3:20 PM</p>
                                    </div>
                                    <a href="#!" class="position-absolute top-10 end-0 fs-18 z-1 link link-danger me-3"><i class="bi bi-x"></i></a>
                                </div>
                                <div class="noti-item">
                                    <img src="views/assets/images/avatar/avatar-1.jpg" alt="Avatar Image" class="avatar-md">
                                    <div>
                                        <a href="#!" class="stretched-link">
                                            <h6 class="mb-2 text-body">Mia <strong class="fw-normal text-muted fs-13">shared a file in Marketing Campaign</strong></h6>
                                        </a>
                                        <p class="text-muted mb-3 fs-12">Thursday 3:20 PM</p>
                                        <div class="d-flex align-items-center gap-2 p-2 position-relative z-1 border rounded">
                                            <div class="avatar-md d-flex align-items-center rounded justify-content-center flex-shrink-0 bg-danger-subtle text-danger">
                                                <i class="bi bi-file-pdf"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#!">
                                                    <h6 class="mb-2">Campaign_Strategy.mp4</h6>
                                                </a>
                                                <p class="mb-0 text-muted fs-12">MP4 | 14 MB</p>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="#!" class="position-absolute top-10 end-0 fs-18 z-1 link link-danger me-3"><i class="bi bi-x"></i></a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (($role ?? "") === "admin"): ?>
                                <div class="p-2 border-top d-grid">
                                    <a href="incident-reports" class="btn btn-sm btn-light">View incident reports</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="dark-mode-btn" id="toggleMode">
                    <button class="btn header-btn active" id="lightModeBtn" type="button" aria-label="Switch to light mode">
                        <i class="bi bi-brightness-high"></i>
                    </button>
                    <button class="btn header-btn" id="darkModeBtn" type="button" aria-label="Switch to Dark mode">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                </div>
                <div class="dropdown pe-dropdown-mega d-none d-md-block">
                    <button class="header-profile-btn btn gap-1 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-none d-xl-block pe-2">
                            <span class="d-block mb-0 fs-12 fw-semibold"><?php echo $fullName; ?></span>
                            <span class="d-block mb-0 fs-10 text-muted"><?php echo $email; ?></span>
                        </div>
                        <span class="header-btn btn position-relative">
                            <img src="views/assets/images/avatar/avatar-3.jpg" alt="Avatar Image" class="img-fluid rounded-circle">
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-mega-sm header-dropdown-menu p-3">
                        <div class="border-bottom pb-2 mb-2 d-flex align-items-center gap-2">
                            <img src="views/assets/images/avatar/avatar-3.jpg" alt="Avatar Image" class="avatar-md">
                            <div>
                                <a href="javascript:void(0)">
                                    <h6 class="mb-0 lh-base"><?php echo $fullName; ?></h6>
                                </a>
                                <p class="mb-0 fs-13 text-muted"><?php echo $email; ?></p>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-1 border-bottom pb-1">
                            <li><a class="dropdown-item" href="user-profile"><i class="bi bi-person me-2"></i> View Profile</a></li>
                        </ul>
                        <ul class="list-unstyled mb-0">
                            <li><a href="logout" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- END Header -->

<?php if (($role ?? "") === "admin"): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var button = document.getElementById('adminNotificationButton');
    var items = Array.prototype.slice.call(document.querySelectorAll('.admin-noti-item[data-notification-key]'));
    var countBadge = document.querySelector('.admin-notification-count');
    var summaryBadge = document.querySelector('.admin-notification-summary');
    var dot = document.querySelector('.admin-notification-dot');

    if (!button) {
        return;
    }

    var adminID = button.getAttribute('data-admin-id') || 'admin';
    var storageKey = 'almodiel.admin.notifications.seen.' + adminID;

    function readSeenKeys() {
        try {
            var stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return Array.isArray(stored) ? stored : [];
        } catch (error) {
            return [];
        }
    }

    function currentKeys() {
        return items
            .map(function (item) { return item.getAttribute('data-notification-key') || ''; })
            .filter(Boolean);
    }

    function unique(values) {
        return values.filter(function (value, index) {
            return values.indexOf(value) === index;
        });
    }

    function updateBadge() {
        var seen = readSeenKeys();
        var unread = currentKeys().filter(function (key) {
            return seen.indexOf(key) === -1;
        });
        var unreadCount = unread.length;

        if (countBadge) {
            countBadge.textContent = unreadCount > 99 ? '99' : String(unreadCount);
            countBadge.classList.toggle('d-none', unreadCount === 0);
        }

        if (dot) {
            dot.classList.toggle('d-none', unreadCount === 0);
        }

        if (summaryBadge) {
            summaryBadge.textContent = unreadCount + ' new';
            summaryBadge.classList.toggle('bg-primary-subtle', unreadCount > 0);
            summaryBadge.classList.toggle('text-primary', unreadCount > 0);
            summaryBadge.classList.toggle('bg-secondary-subtle', unreadCount === 0);
            summaryBadge.classList.toggle('text-secondary', unreadCount === 0);
        }
    }

    function markCurrentAsSeen() {
        var seen = readSeenKeys();
        var merged = unique(seen.concat(currentKeys())).slice(-100);
        localStorage.setItem(storageKey, JSON.stringify(merged));
        updateBadge();
    }

    button.addEventListener('shown.bs.dropdown', markCurrentAsSeen);
    button.addEventListener('click', function () {
        setTimeout(markCurrentAsSeen, 150);
    });

    updateBadge();
});
</script>
<?php endif; ?>

<!-- Search Modal -->
<div class="modal fade search-modal" id="searchModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header d-block">
        <div class="form-icon">
          <input type="text" class="form-control form-control-icon" id="searchInputInModal" placeholder="Search" required>
          <div class="search-btn w-44px">
            <i class="ri-search-line text-muted fs-16"></i>
          </div>
          <button type="button" class="btn-close position-absolute end-0 top-50 translate-middle-y d-inline-block m-0" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body" data-simplebar id="list-items">
        <ul class="list-unstyled mb-0" id="searchList"></ul>
      </div>
    </div>
  </div>
</div>
