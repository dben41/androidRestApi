<?php
/*
 GET   /api/lesson                   get all the lessons (useless, i won't do this)
 GET   /api/lesson/{uid}             get all the lessons based on {uid}
 GET   /api/lesson/class/{class_id}   get all the lessons based on a class. (won't do this.)
 POST  /api/lesson/add
 */

$lid = "lid";
$uid = "uid"; //foreign key
$date = "date";
$content = "content";

$lessonKeys = array(
	$lid=>0,
	$uid=>0,
	$date=>0,
	$content=>0
);

$resourceKeys = array_merge(array_diff(explode("/", $_SERVER['REQUEST_URI']), array("", "lesson")));

/**
 * Opens and returns a MYSQL DB connection, using PDO
 */
function openDBConnection () {
	//echo "Opening the DB Connection.";
	//credentials for the db
	$DBH = new PDO("mysql:host=localhost:3306;dbname=guitar_teacher", 
			       'root', '');
    $DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $DBH;
}

if($_SERVER['REQUEST_METHOD'] == "GET")
{
	if(count($resourceKeys) == 1) {
		$lesson = null;
		try {
			//open db connection
			$DBH = openDBConnection();
			$stmt = $DBH->prepare("SELECT * FROM lesson WHERE uid = ?");
			//get the uid
			$uidFromUrl = $resourceKeys[0];
			$stmt->bindValue(1, $uidFromUrl);
			$stmt->execute();
			
			//load the variable
			$lessons = array();
			$result = $stmt-> fetchAll();
			foreach($result as $row) {
				//stuff into array
				$lesson = array_intersect_key($row, $lessonKeys);
				array_push($lessons, $lesson);
			}
			
		} catch (PDOException $e) {
			reportDBError($e);
		}
		if($lesson != null)
		{
			header("Content-type: application/json");
			print(json_encode(array('lessons' =>$lessons)));
		} else {
			header("Content-type: application/json");
			print(json_encode("Bad Request! '" . $resourceKeys[0] . "' is not a valid resource!"));
		}
	}
}  else if($_SERVER['REQUEST_METHOD'] == "POST") {

	if(count($resourceKeys) == 1){
		//Convert to JSON and transmit
			header("Content-type: text/plain");
			//print "It's a post!!";
			$postData = file_get_contents("php://input");
			$lesson = json_decode($postData, true);

			//check if empty
			if(empty($lesson))
				die("Lesson contains no data!");

			try{
				$DBH = openDBConnection();
				$DBH->beginTransaction();
				$stmt = $DBH->prepare("INSERT INTO lesson (uid, date, content) VALUES(?,?,?);");
				$stmt->bindValue(1, $lesson["uid"]);
				$stmt->bindValue(2, $lesson["date"]);
				$stmt->bindValue(3, $lesson["content"]);
				$stmt->execute();
				$DBH->commit();
				//return success message
				print(json_encode("Lesson inserted successfully!"));

			}catch(PDOException $e) {
				print_r($user);
				die("Problem inserting into DB!");
				reportDBError($e);
			}

	}
}

?>
