<?php
require_once "controllers/template.controller.php";

require_once "controllers/customer.controller.php";
require_once "models/customer.model.php";

require_once "controllers/employee.controller.php";
require_once "models/employee.model.php";

// require_once "controllers/userrights.controller.php";
// require_once "models/userrights.model.php";

$template = new ControllerTemplate();
$template -> ctrTemplate();