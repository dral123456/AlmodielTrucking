<?php
class ControllerLocation {

  static public function ctrSaveLocation($data) {
    return (new ModelLocation)->mdlSaveLocation($data);
  }

  static public function ctrSaveOrReuseLocation($data) {
    return (new ModelLocation)->mdlSaveOrReuseLocation($data);
  }

  static public function ctrSearchLocations($query, $limit = 8) {
    return (new ModelLocation)->mdlSearchLocations($query, $limit);
  }
}