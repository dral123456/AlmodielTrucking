<?php
class ControllerTariff {
  static public function ctrLookupTariff($data) {
    return ModelTariff::mdlLookupTariff($data);
  }

  static public function ctrCompanyList() {
    return ModelTariff::mdlCompanyList();
  }

  static public function ctrTariffRows($customerID = null) {
    return ModelTariff::mdlTariffRows($customerID);
  }

  static public function ctrSaveTariff($data) {
    return ModelTariff::mdlSaveTariff($data);
  }

  static public function ctrArchiveTariff($tariffID) {
    return ModelTariff::mdlArchiveTariff($tariffID);
  }

  static public function ctrBulkUpdateFuelRange($data) {
    return ModelTariff::mdlBulkUpdateFuelRange($data);
  }

  static public function ctrImportTariffCsv($rows, $defaults) {
    return ModelTariff::mdlImportTariffCsv($rows, $defaults);
  }
}
