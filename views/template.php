<!DOCTYPE html>
<?php
  session_start();
?>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <script>
    (function() {
      var theme = localStorage.getItem('data-bs-theme') || 'light';
      var sidebar = localStorage.getItem('data-sidebar') || 'default';
      var sidebarColor = localStorage.getItem('data-sidebar-color') || 'light';
      var layout = localStorage.getItem('data-layout') || 'vertical';
      var themeColor = localStorage.getItem('data-theme-colors') || 'default';

      document.documentElement.setAttribute('data-bs-theme', theme);
      document.documentElement.setAttribute('data-sidebar', sidebar);
      document.documentElement.setAttribute('data-sidebar-color', sidebarColor);
      document.documentElement.setAttribute('data-layout', layout);
      document.documentElement.setAttribute('data-theme-colors', themeColor);
    })();
  </script>
  <title>Almodiel Trucking Service </title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <meta content="Admin & Dashboards Template" name="description" />
  <meta content="Pixeleyez" name="author" />
  
  <!-- layout setup -->
  <script type="module" src="views/assets/js/layout-setup.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var theme = localStorage.getItem('data-bs-theme') || 'light';
      var lightBtn = document.getElementById('lightModeBtn');
      var darkBtn  = document.getElementById('darkModeBtn');

      if (!lightBtn || !darkBtn) return;

      if (theme === 'dark') {
        lightBtn.classList.remove('active');
        darkBtn.classList.add('active');
      } else {
        lightBtn.classList.add('active');
        darkBtn.classList.remove('active');
      }
    });
  </script>
  
  <!-- App favicon -->
  <link rel="shortcut icon" href="views/assets/images/favicon.png">  <!-- Simplebar Css -->
  <link rel="stylesheet" href="views/assets/libs/simplebar/simplebar.min.css">
  <!-- Swiper Css -->
  <link href="views/assets/libs/swiper/swiper-bundle.min.css" rel="stylesheet">
  <!-- Nouislider Css -->
  <link href="views/assets/libs/nouislider/nouislider.min.css" rel="stylesheet">
  <!-- Bootstrap Css -->
  <link href="views/assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css">
  <!--icons css-->
  <link href="views/assets/css/icons.min.css" rel="stylesheet" type="text/css">
  <!-- App Css-->
  <link href="views/assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css">
  <link rel="stylesheet" href="views/assets/libs/choices.js/public/assets/styles/choices.min.css">

  <!-- MY CSS -->
  <link rel="stylesheet" href="views/css/stepper.css">
  <!-- <link rel="stylesheet" href="views/css/datepicker.css"> -->

  <!-- DATE PICKER -->
  <link rel="stylesheet" href="views/assets/libs/air-datepicker/air-datepicker.css">
  <link rel="stylesheet" href="views/assets/libs/leaflet/leaflet.css">

  <script src="views/assets/js/jquery-4.0.0.min.js"></script>
  <style>
    .layout-container {
      display: flex;
    }

    .layout-page {
      flex: 1;
      min-width: 0;
      overflow-x: hidden;
      margin-left: var(--pe-app-sidebar-width) !important;
      width: calc(100% - var(--pe-app-sidebar-width)) !important;
      transition: margin-left 0.2s ease, width 0.2s ease;
    }

    .content-wrapper {
      padding-top: 74px !important;
    }

    html[data-sidebar="icon"] .layout-page,
    html[data-sidebar="icon-hover"] .layout-page {
      margin-left: var(--pe-app-sidebar-sm-width) !important;
      width: calc(100% - var(--pe-app-sidebar-sm-width)) !important;
    }

    html[data-sidebar="medium"] .layout-page {
      margin-left: var(--pe-app-sidebar-medium-width) !important;
      width: calc(100% - var(--pe-app-sidebar-medium-width)) !important;
    }

    @media (max-width: 1199.98px) {
      .layout-page {
        margin-left: 0 !important;
        width: 100% !important;
      }

      .content-wrapper {
        padding-top: 64px !important;
      }

      .container-fluid {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
      }
    }

    @media (max-width: 575.98px) {
      .container-fluid {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
      }

      .card-header,
      .card-body {
        padding: 1rem !important;
      }
    }
</style>

</head>

<body>

  <?php 
    if(!(isset($_SESSION["loggedIn"]) && $_SESSION["loggedIn"] == "ok") && isset($_GET["route"])){
      $route = basename($_GET["route"]);
      $allowedRoutes = [
          'customer-login',
          'signup',
          'staff-login',
          'driver-login',
          'admin-login',
          'assistant-login',
          'map',  
          // 'home',
          // 'staffclinic',
          // 'logout'
      ];
      if (in_array($route, $allowedRoutes)) {
        if($route == "signup"){
          include "modules/customer-individual/" . $route . ".php";
        }else{
          include "modules/" . $route . ".php";
        }
      } else {
          include "modules/404.php";
      }
    }
    else if(isset($_SESSION["loggedIn"]) && $_SESSION["loggedIn"] == "ok"){
      $role = $_SESSION["role"] ?? 'customer';
      if (in_array($role, ['customer-individual', 'customer-company'], true)) {
        $role = 'customer';
      }
      $routeMap   = include "configs/routes.php";
      $modulePaths = include "configs/module-paths.php";

      $allowedRoutes = $routeMap[$role] ?? [];
      echo '<script>console.log("User role: ' . $role . '")</script>';
      echo '<script>console.log("Allowed routes for this role: ' . implode(", ", $allowedRoutes) . '")</script>';
      echo '<div class="layout-wrapper layout-content-navbar">';
        echo '<div class="layout-container">';
          include "partials/sidebar.php";
          echo '<div class="layout-page">';
            include "partials/header.php";
            echo '<div class="content-wrapper">';
              echo '<div class="container-fluid py-4">';
              $route = isset($_GET["route"]) ? basename($_GET["route"]) : 'sample';
            if (isset($_GET["route"])) {
              $raw = $_GET["route"];
              // Allow only alphanumeric, hyphens, and ONE slash
              if (preg_match('/^[a-zA-Z0-9\-]+(\/[a-zA-Z0-9\-]+)?$/', $raw)) {
                  $route = $raw;
              } else {
                  $route = '404';
              }
            }
          if (in_array($route, $allowedRoutes) && isset($modulePaths[$route])) {
            include $modulePaths[$route];
          } elseif (isset($modulePaths[$route])) {
            // Route exists but user doesn't have access
            include "modules/403.php";
          } else {
            include "modules/404.php";
          }
              echo '</div>'; // container-fluid
            echo '</div>'; // content-wrapper

          echo '<div class="layout-overlay layout-menu-toggle"></div>';
          echo '<div class="drag-target"></div>';

          echo '</div>'; // layout-page
        echo '</div>'; // layout-container
      echo '</div>'; // layout-wrapper
    }else{
      include "modules/customer-login.php";
    }
  ?>

<!-- LOGIN -->
<script src="views/assets/libs/swiper/swiper-bundle.min.js"></script>
<script src="views/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="views/assets/libs/simplebar/simplebar.min.js"></script>
<script src="views/assets/js/scroll-top.init.js"></script>
<script src="views/assets/js/auth/auth.init.js"></script>

<!-- DATE PICKER -->
<script src="views/assets/libs/air-datepicker/air-datepicker.js"></script>
<!-- <script src="views/assets/js/ui/air-datepicker.init.js"></script> -->

<!-- LIBS -->
<script src="views/assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="views/assets/libs/leaflet/leaflet.js"></script>

<!-- ICONS
<script src="views/assets/js/icon/icons-remix.init.js"></script> -->

<!-- FORMS -->
<script src="views/assets/js/form/advanced-form.init.js"></script>
<script src="views/assets/js/form/file-upload.init.js"></script>
<script src="views/assets/js/form/form-editor.init.js"></script>
<!-- <script src="views/assets/js/form/form-layout.init.js"></script> -->
<script src="views/assets/js/form/form-validation.init.js"></script>
<script src="views/assets/js/form/forms-select.init.js"></script>
<script src="views/assets/js/form/stepper.init.js"></script>

<!-- SWEET ALERT -->
<script src="views/assets/libs/sweetalert2/sweetalert2.all.min.js"></script>
<script src="views/assets/js/ui/sweetalert.init.js"></script>





<?php
  if (isset($route)) {
    $routeScripts = [
      "customer-reg" => ["customer-reg.js"],
      "employee-reg" => ["employee-reg.js"],
      "truck-reg" => ["admin/truck-reg.js"],
      "booking-reg" => ["booking-reg.js"],
      "signup" => ["customer-individual/signup.js"],
      "trips" => ["trips.js"],
      "reports" => ["admin/reports.js"],
      "manage-company" => ["admin/manage.js"],
      "manage-employee" => ["admin/manage.js"],
      "manage-truck" => ["admin/manage.js"]
    ];

    if (array_key_exists($route, $routeScripts)) {
      foreach ($routeScripts[$route] as $script) {
        $scriptPath = "views/js/" . $script;
        if (file_exists($scriptPath)) {
          echo '<script src="/almodieltrucking/' . $scriptPath . '?v=' . filemtime($scriptPath) . '"></script>';
        }
      }
    }
  }
?>

</body>

</html>
