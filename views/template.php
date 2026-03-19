<!DOCTYPE html>
<?php
  session_start();
?>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Almodiel Trucking Service </title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <meta content="Admin & Dashboards Template" name="description" />
  <meta content="Pixeleyez" name="author" />
  
  <!-- layout setup -->
  <script type="module" src="views/assets/js/layout-setup.js"></script>
  
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
</head>

<body>
  <?php 
    if(isset($_SESSION["loggedIn"]) && $_SESSION["loggedIn"] == "ok"){
      // echo '<div class="layout-wrapper layout-content-navbar">';
      //   echo '<div class="layout-container">';
      //     //include "modules/sidebar.php";
      //     echo '<div class="menu-mobile-toggler d-xl-none rounded-1">';
      //       echo '<a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">';
      //       echo '<i class="ti tabler-menu icon-base"></i>';
      //       echo '<i class="ti tabler-chevron-right icon-base"></i>';
      //     echo '</a>';
      //     echo '</div>';

      //     echo '<div class="layout-page">';
      //       //include "modules/navbar.php";
      //       echo '<div class="content-wrapper">';
            if(isset($_GET["route"])){
              $route = basename($_GET["route"]);
              $allowedRoutes = [
                  'sample'
                  // 'home',
                  // 'staffclinic',
                  // 'logout'
              ];

              if (in_array($route, $allowedRoutes)) {
                  include "modules/" . $route . ".php";
              } else {
                  include "modules/404.php";
              }
            }else{
              $route = "sample";
              include "modules/sample.php"; 
            }
      //       echo '</div>';
      //       echo '<div class="layout-overlay layout-menu-toggle"></div>';
      //       echo '<div class="drag-target"></div>';
      //     echo '</div>';
      //   echo '</div>';
      // echo '</div>';
    }else{
      include "modules/login.php";
    }
  ?>

<!-- LOGIN -->
<script src="views/assets/libs/swiper/swiper-bundle.min.js"></script>
<script src="views/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="views/assets/libs/simplebar/simplebar.min.js"></script>
<script src="views/assets/js/scroll-top.init.js"></script>
<script src="views/assets/js/auth/auth.init.js"></script>

</body>

</html>