<?php
class ControllerTruck {
  static public function ctrTruckManageList() {
    return (new ModelTruck)->mdlTruckManageList();
  }

  static public function ctrEmployeeListByType($type) {
    return (new ModelTruck)->mdlEmployeeListByType($type);
  }

  static public function ctrSaveTruck($data) {
    return (new ModelTruck)->mdlSaveTruck($data);
  }
}
