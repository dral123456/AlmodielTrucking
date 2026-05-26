<?php
class ControllerEmployee {
  static public function ctrEmployeeList() {
    return (new ModelEmployee)->mdlEmployeeList();
  }

  static public function ctrSaveEmployee($data) {
    return (new ModelEmployee)->mdlSaveEmployee($data);
  }

  static public function ctrAssistantLogin() {
    if (isset($_POST["loginAssistant"])) {
      $encryptpass = $_POST["password"];
      $table = 'employee';
      $item = 'empPhoneNumber';
      $value = $_POST["phoneNumber"];
      $answer = (new ModelEmployee)->mdlGetEmployeeCredentials($table, $item, $value);

      if (!empty($answer) && $answer["empPhoneNumber"] == $_POST["phoneNumber"] && self::verifyPassword($encryptpass, $answer["empPassword"])) {
        $_SESSION["loggedIn"] = "ok";
        $_SESSION["id"] = $answer["id"];
        $_SESSION["empType"] = $answer["empType"];
        $_SESSION["role"] = self::sessionRoleForEmployee($answer["empType"]);

        echo '<script>
          window.location = "sample";
        </script>';
      } else {
        echo '<br><div style="text-align:center;" class="alert alert-danger">User or password incorrect</div>';
      }
    }
  }

  static public function ctrDriverLogin() {
    if (isset($_POST["loginDriver"])) {
      $encryptpass = $_POST["password"];
      $table = 'employee';
      $item = 'empPhoneNumber';
      $value = $_POST["phoneNumber"];
      $answer = (new ModelEmployee)->mdlGetEmployeeCredentials($table, $item, $value);

      if (!empty($answer) && $answer["empPhoneNumber"] == $_POST["phoneNumber"] && self::verifyPassword($encryptpass, $answer["empPassword"])) {
        $_SESSION["loggedIn"] = "ok";
        $_SESSION["id"] = $answer["id"];
        $_SESSION["empType"] = $answer["empType"];
        $_SESSION["role"] = self::sessionRoleForEmployee($answer["empType"]);

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


      if (!empty($answer) && $answer["empPhoneNumber"] == $_POST["phoneNumber"] && self::verifyPassword($encryptpass, $answer["empPassword"])) {
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

  static private function verifyPassword($plainPassword, $storedPassword) {
    $storedPassword = (string) $storedPassword;

    if ($storedPassword === "") {
      return false;
    }

    if (password_get_info($storedPassword)["algo"] !== 0) {
      return password_verify($plainPassword, $storedPassword);
    }

    return hash_equals($storedPassword, $plainPassword);
  }

  static private function sessionRoleForEmployee($empType) {
    $empType = strtolower(trim((string) $empType));

    if (in_array($empType, ["admin", "driver", "assistant", "employee"], true)) {
      return $empType;
    }

    return "employee";
  }
}
