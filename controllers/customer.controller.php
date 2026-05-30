<?php
class ControllerCustomer{
	static public function ctrCompanyList(){
	  $answer = (new ModelCustomer)->mdlCompanyList();
		return $answer;
	}

	static public function ctrSaveCustomer($data){
	  $answer = (new ModelCustomer)->mdlSaveCustomer($data);
		return $answer;
	}
  static public function ctrCustomerLogin(){
		if (isset($_POST["loginUser"])) {
      $encryptpass = $_POST["password"];
      $table = 'customer';
      $item = 'phoneNumber';
      $value = $_POST["phoneNumber"];
      $answer = (new ModelCustomer)->mdlGetCustomerCredentials($table, $item, $value);

      if(!empty($answer) && $answer["phoneNumber"] == $_POST["phoneNumber"] && self::verifyPassword($encryptpass, $answer["password"])){
        $_SESSION["loggedIn"] = "ok";
        $_SESSION["id"] = $answer["id"];
        
        $_SESSION["customerType"] = $answer["customerType"];
        $_SESSION["role"] = "customer" . "-" . $_SESSION["customerType"];
        $_SESSION["fname"] = $answer["customerFName"];
        $_SESSION["MI"] = $answer["customerMI"];
        $_SESSION["lname"] = $answer["customerLName"];
        $_SESSION["fullname"] = $answer["customerFName"] . " " . $answer["customerMI"] . " " . $answer["customerLName"];
        
        // $empid = $_SESSION["empid"];
        //$answer = (new ModelUserRights)->mdlAddLogin($empid);
          
              echo '<script>
                window.location = "sample";
              </script>';
          
      }else{
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
}
