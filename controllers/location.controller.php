<?php
class ControllerLocation {

  static public function ctrSaveLocation($data) {
    return (new ModelLocation)->mdlSaveLocation($data);
  }
}