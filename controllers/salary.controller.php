<?php
class ControllerSalary {
  static public function ctrSalaryRows($employeeID = null, $status = "all") {
    return ModelSalary::mdlSalaryRows($employeeID, $status);
  }

  static public function ctrDeliveredTripOptions() {
    return ModelSalary::mdlDeliveredTripOptions();
  }

  static public function ctrSaveSalary($data) {
    return ModelSalary::mdlSaveSalary($data);
  }

  static public function ctrMarkSalaryPaid($salaryID) {
    return ModelSalary::mdlMarkSalaryPaid($salaryID);
  }
}
