<?php
class ControllerEmployee {
  static public function ctrEmployeeList() {
    return (new ModelEmployee)->mdlEmployeeList();
  }

  static public function ctrSaveEmployee($data) {
    return (new ModelEmployee)->mdlSaveEmployee($data);
  }

  static public function ctrDriverLogin() {
    if (isset($_POST["loginDriver"])) {
      $encryptpass = $_POST["password"];
      $table = 'employee';
      $item = 'empPhoneNumber';
      $value = $_POST["phoneNumber"];
      $empType = 'driver';
      $answer = (new ModelEmployee)->mdlGetEmployeeCredentials($table, $item, $value, $empType);

      if (!empty($answer) && $answer["empPhoneNumber"] == $_POST["phoneNumber"] && password_verify($encryptpass, $answer["empPassword"])) {
        $_SESSION["loggedIn"] = "ok";
        $_SESSION["id"] = $answer["id"];
        $_SESSION["empType"] = $answer["empType"];
        $_SESSION["role"] = "driver";

        echo '<script>
          window.location = "sample";
        </script>';
      } else {
        echo '<br><div style="text-align:center;" class="alert alert-danger">User or password incorrect</div>';
      }
    }
  }

  static public function ctrAdminLogin() {
    if (isset($_POST["loginAdmin"])) {
      $encryptpass = $_POST["password"];
      $table = 'employee';
      $item = 'empPhoneNumber';
      $value = $_POST["phoneNumber"];
      $empType = 'admin';
      $answer = (new ModelEmployee)->mdlGetEmployeeCredentials($table, $item, $value, $empType);

      if (!empty($answer) && $answer["empPhoneNumber"] == $_POST["phoneNumber"] && password_verify($encryptpass, $answer["empPassword"])) {
        $_SESSION["loggedIn"] = "ok";
        $_SESSION["id"] = $answer["id"];
        $_SESSION["empType"] = $answer["empType"];
        $_SESSION["role"] = "admin";

        echo '<script>
          window.location = "sample";
        </script>';
      } else {
        echo '<br><div style="text-align:center;" class="alert alert-danger">User or password incorrect</div>';
      }
    }
  }
}
