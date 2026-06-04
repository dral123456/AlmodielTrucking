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

  static public function ctrTruckDetails($truckID) {
    return (new ModelTruck)->mdlTruckDetails($truckID);
  }

  static public function ctrSaveTruckFuelLog($data) {
    return (new ModelTruck)->mdlSaveTruckFuelLog($data);
  }

  static public function ctrUpdateTruckReadings($data) {
    return (new ModelTruck)->mdlUpdateTruckReadings($data);
  }
}
