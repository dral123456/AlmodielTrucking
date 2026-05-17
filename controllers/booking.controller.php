<?php
class ControllerBooking {
  static public function ctrCustomerList() {
    return (new ModelBooking)->mdlCustomerList();
  }

  static public function ctrTripList() {
    return (new ModelBooking)->mdlTripList();
  }

  static public function ctrTruckList() {
    return (new ModelBooking)->mdlTruckList();
  }

  static public function ctrEmployeeListByType($type) {
    return (new ModelBooking)->mdlEmployeeListByType($type);
  }

  static public function ctrTruckDefaultCrew($truckID) {
    return (new ModelBooking)->mdlTruckDefaultCrew($truckID);
  }

  static public function ctrTripOverviewList() {
    return (new ModelBooking)->mdlTripOverviewList();
  }

  static public function ctrSaveBooking($data) {
    return (new ModelBooking)->mdlSaveBooking($data);
  }
}
