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

  static public function ctrTripOverviewList($employeeID = 0, $employeeRole = "") {
    return (new ModelBooking)->mdlTripOverviewList($employeeID, $employeeRole);
  }

  static public function ctrDriverTripList($driverID, $showAll = false) {
    return (new ModelBooking)->mdlDriverTripList($driverID, $showAll);
  }

  static public function ctrUpdateTripDeliveryStatus($tripID, $status, $driverID, $showAll = false) {
    return (new ModelBooking)->mdlUpdateTripDeliveryStatus($tripID, $status, $driverID, $showAll);
  }

  static public function ctrUpdateTripInfo($tripID, $data) {
    return (new ModelBooking)->mdlUpdateTripInfo($tripID, $data);
  }

  static public function ctrSaveBooking($data) {
    return (new ModelBooking)->mdlSaveBooking($data);
  }

  static public function ctrCustomerBookingList($customerID) {
    return (new ModelBooking)->mdlCustomerBookingList($customerID);
  }
  static public function ctrGetBooking($bookingID) {
    return (new ModelBooking)->mdlGetBooking($bookingID);
  }
  
  static public function ctrReceiptBooking(int $bookingID): ?array {
    return ModelBooking::mdlReceiptBooking($bookingID);
  }

  static public function ctrReceiptCargoItems(int $bookingID): array {
      return ModelBooking::mdlReceiptCargoItems($bookingID);
  }

  static public function ctrReceiptTripCrew(int $tripID): array {
      return ModelBooking::mdlReceiptTripCrew($tripID);
  }


}
