<?php

class ControllerEmployee {

    static public function ctrEmployeeList() {
        $answer = ModelEmployee::mdlEmployeeList();
        return $answer;
    }

    static public function ctrSaveEmployee($data) {
        $answer = ModelEmployee::mdlSaveEmployee($data);
        return $answer;
    }

}
