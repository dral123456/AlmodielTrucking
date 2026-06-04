<?php
class ControllerSales {
  static public function ctrSalesDashboard($filters) {
    return ModelSales::mdlSalesDashboard($filters);
  }

  static public function ctrMarkSalesAsPaid($bookingID) {
    return ModelSales::mdlMarkSalesAsPaid($bookingID);
  }

  static public function ctrMarkSalesGroupAsPaid($filters) {
    return ModelSales::mdlMarkSalesGroupAsPaid($filters);
  }
}
