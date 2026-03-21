<?php
class ControllerUserRights{
	static public function ctrUserLogin(){
		if (isset($_POST["loginUser"])) {
				$encryptpass = $_POST["password"];
				$table = 'userrights';
				$item = 'username';
				$value = $_POST["username"];
				$answer = (new ModelUserRights)->mdlGetUserCredentials($table, $item, $value);

				if(!empty($answer) && $answer["username"] == $_POST["username"] && $answer["upassword"] == $encryptpass){
					$_SESSION["loggedIn"] = "ok";
					$_SESSION["id"] = $answer["id"];
					
					$_SESSION["empid"] = $answer["empid"];
					$_SESSION["userid"] = $answer["userid"];
					
					$empid = $_SESSION["empid"];
					//$answer = (new ModelUserRights)->mdlAddLogin($empid);
				    
                        echo '<script>
									window.location = "sample";
								</script>';
				    
				}else{
					echo '<br><div style="text-align:center;" class="alert alert-danger">User or password incorrect</div>';
				}
			
		}
	}
}