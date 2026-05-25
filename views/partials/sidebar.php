<?php
$allRoutes   = require 'configs/routes.php';
$modulePaths = require 'configs/module-paths.php';
$role        = $_SESSION['role'] ?? 'customer';
$allowed     = $allRoutes[$role] ?? [];

// Define sidebar structure — only items whose route is in $allowed will show
$sidebarItems = [
  [
    'type'  => 'title',
    'label' => 'Forms',
  ],
  [
    'type'  => 'group',
    'icon'  => 'ri-dashboard-line',
    'label' => 'Registration',
    'id'    => 'collapseRegistration',
    'items' => [
      ['label' => 'Individual Customer', 'route' => 'signup'],
      ['label' => 'Booking',             'route' => 'booking-reg'],
      ['label' => 'Trips',               'route' => 'trips'],
      ['label' => 'Driver Trips',        'route' => 'driver-trips'],
    ],
  ],
  [
    'type'  => 'title',
    'label' => 'Management',
  ],
  [
    'type'  => 'group',
    'icon'  => 'ri-settings-3-line',
    'label' => 'Manage Records',
    'id'    => 'collapseManagement',
    'items' => [
      ['label' => 'Company',  'route' => 'manage-company'],
      ['label' => 'Employee', 'route' => 'manage-employee'],
      ['label' => 'Tariff',   'route' => 'manage-tariff'],
      ['label' => 'Truck',    'route' => 'manage-truck'],
    ],
  ],
  [
    'type'  => 'title',
    'label' => 'Reports',
  ],
  [
    'type'  => 'link',
    'icon'  => 'ri-line-chart-line',
    'label' => 'Sales',
    'route' => 'sales',
  ],
  [
    'type'  => 'link',
    'icon'  => 'ri-file-chart-line',
    'label' => 'Reports',
    'route' => 'reports',
  ],
];

// Pre-compute which titles have visible content
$processedItems = [];
$pendingTitle   = null;

foreach ($sidebarItems as $item) {
  if ($item['type'] === 'title') {
    $pendingTitle = $item;
    continue;
  }

  $isVisible = false;
  if ($item['type'] === 'link') {
    $isVisible = in_array($item['route'], $allowed);
  } elseif ($item['type'] === 'group') {
    $visibleChildren = array_filter($item['items'], fn($c) => in_array($c['route'], $allowed));
    $isVisible = !empty($visibleChildren);
  }

  if ($isVisible && $pendingTitle) {
    $processedItems[] = $pendingTitle;
    $pendingTitle = null;
  }

  if ($isVisible) {
    $processedItems[] = $item;
  }
}
?>


<aside class="pe-app-sidebar" id="sidebar">
    <div class="pe-app-sidebar-logo px-6 d-flex align-items-center position-relative">
        <!--begin::Brand Image-->
        <a href="sample" class="d-flex align-items-end logo-main">
            <img height="35" width="34" class="logo-dark" alt="Dark Logo" src="views/assets/images/logo-md.png">
            <img height="35" width="34" class="logo-light" alt="Light Logo" src="views/assets/images/logo-md-light.png">
            <h3 class="text-body-emphasis fw-bolder mb-0 ms-1">Urbix</h3>
        </a>
        <button type="button" id="sidebarDefaultArrow" class="btn btn-sm p-0 fs-16 text-body-emphasis ms-auto float-end d-none icon-hover-btn d-none"><i class="ri-arrow-right-line fs-5"></i></button>
        <!--end::Brand Image-->
    </div>
    <nav class="pe-app-sidebar-menu nav nav-pills" data-simplebar id="sidebar-simplebar">
        <div class="d-flex align-items-start flex-column w-100">
            <ul class="pe-main-menu list-unstyled"> 
                <?php foreach ($processedItems as $item): ?>

                <?php if ($item['type'] === 'title'): ?>
                    <li class="pe-menu-title"><?= htmlspecialchars($item['label']) ?></li>

                <?php elseif ($item['type'] === 'link' && in_array($item['route'], $allowed)): ?>
                    <li class="pe-slide-item">
                    <a href="<?= htmlspecialchars($item['route']) ?>" class="pe-nav-link">
                        <i class="<?= $item['icon'] ?> pe-nav-icon"></i>
                        <span class="pe-nav-content"><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                    </li>

                <?php elseif ($item['type'] === 'group'):
                    $visibleChildren = array_filter($item['items'], fn($c) => in_array($c['route'], $allowed));
                    if (empty($visibleChildren)) continue;
                ?>
                    <li class="pe-slide pe-has-sub">
                    <a href="#<?= $item['id'] ?>" class="pe-nav-link" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="<?= $item['icon'] ?> pe-nav-icon"></i>
                        <span class="pe-nav-content"><?= htmlspecialchars($item['label']) ?></span>
                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                    </a>
                    <ul class="pe-slide-menu collapse" id="<?= $item['id'] ?>">
                        <?php foreach ($visibleChildren as $child): ?>
                        <li class="pe-slide-item">
                            <a href="<?= htmlspecialchars($child['route']) ?>" class="pe-nav-link">
                            <?= htmlspecialchars($child['label']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</aside>
