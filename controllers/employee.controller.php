<?php

class ControllerEmployee {

    static public function ctrSaveEmployee($data) {
        $answer = ModelEmployee::mdlSaveEmployee($data);
        return $answer;
    }

}