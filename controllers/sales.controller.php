<?php
class ControllerSales {
  static public function ctrSalesDashboard($filters) {
    return ModelSales::mdlSalesDashboard($filters);
  }
}
