<?php
class ControllerCustomer{
	static public function ctrSaveCustomer($data){
	  $answer = (new ModelCustomer)->mdlSaveCustomer($data);
		return $answer;
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