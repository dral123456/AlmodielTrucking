<?php
class ControllerIncident {
  static public function ctrSaveIncident($data) {
    return ModelIncident::mdlSaveIncident($data);
  }

  static public function ctrIncidentList() {
    return ModelIncident::mdlIncidentList();
  }

  static public function ctrUpdateIncidentStatus($incidentID, $status, $adminNotes, $reviewedBy) {
    return ModelIncident::mdlUpdateIncidentStatus($incidentID, $status, $adminNotes, $reviewedBy);
  }
}
