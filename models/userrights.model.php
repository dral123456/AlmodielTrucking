<?php
require_once "connection.php";
class ModelUserRights{
	static public function mdlGetUserCredentials($tableUsers, $item, $value){
		$stmt = (new Connection)->connect()->prepare("SELECT * FROM $tableUsers WHERE $item = :$item");
		$stmt -> bindParam(":".$item, $value, PDO::PARAM_STR);
		$stmt -> execute();
		return $stmt -> fetch();
	}

	static public function mdlGetUserLogin($username, $upassword){
		$encryptpass = $upassword;
		$stmt = (new Connection)->connect()->prepare("SELECT userid, username, upassword FROM userrights WHERE (username = '$username') AND (upassword = '$encryptpass')");
		$stmt -> execute();
		return $stmt -> fetch();
	}

	static public function mdlAddLogin($empid){
		$db = new Connection();
		$pdo = $db->connect();
        try{
        	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->beginTransaction();

			date_default_timezone_set('Asia/Manila');

			$emp_id = $empid;
			$currentdate = date('Y-m-d');
			$currenttime = date('h:i A');
			$currentday = date("l");
			$stmt = (new Connection)->connect()->prepare("INSERT INTO logintracker(empid, cdate, ctime, cday) VALUES (:empid, :cdate, :ctime, :cday)");
			$stmt -> bindParam(":empid", $emp_id, PDO::PARAM_STR);
			$stmt -> bindParam(":cdate", $currentdate, PDO::PARAM_STR);
			$stmt -> bindParam(":ctime", $currenttime, PDO::PARAM_STR);
			$stmt -> bindParam(":cday", $currentday, PDO::PARAM_STR);
			
			$stmt->execute();
		    $pdo->commit();
		    return "ok";
		}catch (Exception $e){
			$pdo->rollBack();
			return "error";
		}	
		$pdo = null;	
		$stmt = null;
	}

	static public function mdlShowLoginReport($start_date, $end_date){
		if(!empty($end_date)){
			$dates = " AND (c.cdate BETWEEN '$start_date' AND '$end_date')";
		}else{
			$dates = "";
		}					

		$whereClause = "WHERE (c.empid != 'EM00001')" . $dates;
        
		$stmt = (new Connection)->connect()->prepare("SELECT c.cdate,c.ctime,c.cday,CONCAT(a.fname,' ',a.lname) AS full_name FROM employees AS a INNER JOIN logintracker AS c ON (a.empid = c.empid) $whereClause ORDER BY c.cdate,c.id");

		$stmt -> execute();
		return $stmt -> fetchAll();
		$stmt -> close();
		$stmt = null;
	}		
}