<?php
class ControllerReport {
    static public function ctrSummary() {
        return ModelReport::mdlSummary();
    }

    static public function ctrBillingRows() {
        return ModelReport::mdlBillingRows();
    }

    static public function ctrExpenseRows() {
        return ModelReport::mdlExpenseRows();
    }

    static public function ctrExpenseTruckRows() {
        return ModelReport::mdlExpenseTruckRows();
    }

    static public function ctrSaveExpense($data) {
        return ModelReport::mdlSaveExpense($data);
    }

    static public function ctrStaffRows() {
        return ModelReport::mdlStaffRows();
    }

    static public function ctrSalaryRows() {
        return ModelReport::mdlSalaryRows();
    }

    static public function ctrSaveDeliveryCharge($data) {
        return ModelReport::mdlSaveDeliveryCharge($data);
    }
}
