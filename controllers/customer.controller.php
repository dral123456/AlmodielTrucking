<?php
class ControllerCustomer{
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

      if(!empty($answer) && $answer["phoneNumber"] == $_POST["phoneNumber"] && password_verify($encryptpass, $answer["password"])){
        $_SESSION["loggedIn"] = "ok";
        $_SESSION["id"] = $answer["id"];
        
        $_SESSION["customerType"] = $answer["customerType"];
        $_SESSION["role"] = "customer" . $_SESSION["customerType"];
        
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

	// static public function ctrEditClinicStaff($data){
	//   $answer = (new ModelClinicStaff)->mdlEditClinicStaff($data);
	// }

    // static public function ctrClinicStaffList() {
    //     $answer = (new ModelClinicStaff) -> mdlClinicStaffList();
    //     return $answer;
    // }

    // static public function ctrSearchClinicStaff($empid) {
    //     $answer = (new ModelClinicStaff) -> mdlSearchClinicStaff($empid);
    //     return $answer;
    // }
}