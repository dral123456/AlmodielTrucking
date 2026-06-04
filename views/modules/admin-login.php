
<!-- START -->
<div class="position-fixed top-0 bottom-0 end-0 start-0 z-0 bg-pattern"></div>
<div class="auth-pattern-shapes d-none d-lg-block"></div>
<div class="auth-pattern-outline d-none d-lg-block"></div>
<div class="auth-pattern-shape extra d-none d-lg-block"></div>
<div class="auth-pattern-extra d-none d-lg-block"></div><header class="px-3 px-md-8 py-5 position-absolute top-0 d-flex justify-content-between align-items-center w-100 z-1">
  <a href="staff-login" class="d-flex align-items-end logo-main">
    <img height="35" class="logo-dark" alt="Dark Logo" src="views/assets/images/logo-md.png">
    <h3 class="text-body-emphasis fw-bolder mb-0 ms-1">Urbix</h3>
  </a>
  <ul class="list-inline mb-0">
    <li class="list-inline-item pe-4 border-end"><a href="customer-login" class="link-body-emphasis">Admin Login</a></li>
  </ul>
</header>
<div class="container">
  <div class="row justify-content-center align-items-center min-vh-100 pt-20 pb-10">
    <div class="col-12 col-md-8 col-lg-6 col-xl-5">
      <div class="card mx-xxl-8 shadow-none">
        <div class="card-body p-8">
          <h3 class="fw-medium text-center">Admin Login</h3>
          <p class="mb-8 text-muted text-center">Admin access</p>
          <form id="formAuthentication" method="POST" action="">
            <div class="mb-4">
              <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                id="phoneNumber"
                name="phoneNumber"
                placeholder="Enter admin phone number"
                autofocus
                required>
            </div>
            <div class="mb-4">
              <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input
                  type="password"
                  class="form-control"
                  id="password"
                  name="password"
                  placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                  aria-describedby="password"
                  required>
                <button type="button" class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted toggle-password" id="toggle-password" data-target="password"><i class="ri-eye-off-line align-middle"></i></button>
              </div>
            </div>
            <div>
              <button type="submit" name="loginAdmin" class="btn btn-primary w-100 mb-4">Sign In</button>
              <?php
                $login = new ControllerEmployee();
                $login->ctrAdminLogin();
              ?>
            </div>
          </form>
        </div>
      </div>
      <p class="position-relative text-center fs-13 mb-0">Â©
        <script>document.write(new Date().getFullYear())</script> Urbix. Crafted with by Pixeleyez
      </p>
    </div>
  </div>
</div>
