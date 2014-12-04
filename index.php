<?php
/*
	User REST API Interface:

  GET      /api/users 					Gets all the users
  GET      /api/users/2					Retrieves all users based on primary key
	POST     /api/users/login 				Send a username and password and receive a message.
	POST     /api/users/add    				Create a new user
	*PUT      /api/users/2					Updates user based on primary key
	*DELETE   /api/users/2					Deletes user based on primary key


*/
$uid = "uid";
$name = "name";
$username =  "username";
$password = "password";
$role = "role";

//declare user array
$userKeys = array(
	$uid=>0,
	$name=>0,
	$username=>0,
	$password=>0,
	$role=>0
	);

//parse the requested resource
$resourceKeys = array_merge(array_diff(explode("/", $_SERVER['REQUEST_URI']), array("", "user")));

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

//Process request
if($_SERVER['REQUEST_METHOD'] == "GET")
{
	//check to see if get all users
	if(count($resourceKeys) == 0)
	{
		try {
			//open db connection
			$DBH = openDBConnection();
			$stmt = $DBH->prepare("SELECT * FROM user");
			$stmt->execute();
			
			//load the variable
			$users = array();
			$result = $stmt-> fetchAll();
			foreach($result as $row) {
				//stuff into array
				$user = array_intersect_key($row, $userKeys);
				array_push($users, $user);
			}
		} catch (PDOException $e) {
			reportDBError($e);
		}

		//print_r($users);
		header("Content-type: application/json");
		print(json_encode($users));

	} 
	//check to see if a specific user was specified
	elseif(count($resourceKeys) == 1) 
	{
		$user = null;
		try {
			//open db connection
			$DBH = openDBConnection();
			$stmt = $DBH->prepare("SELECT * FROM user WHERE uid = ?");
			//get the uid
			$uidFromUrl = $resourceKeys[0];
			$stmt->bindValue(1, $uidFromUrl);
			$stmt->execute();
			
			//load the variable
			if($row  = $stmt-> fetch())
				//stuff into array
				$user = array_intersect_key($row, $userKeys);
			
		} catch (PDOException $e) {
			reportDBError($e);
		}
		if($user != null)
		{
			header("Content-type: application/json");
			print(json_encode($user));
		} else {
			header("Content-type: application/json");
			print(json_encode("Bad Request! '" . $resourceKeys[0] . "' is not a valid resource!"));
		}
	}
} else if($_SERVER['REQUEST_METHOD'] == "POST") {
	//there must at least be one resource key
	if(count($resourceKeys) == 0)
	{
		header("Content-type: application/json");
		print(json_encode("Bad Request! Please specify 'add' or 'login' "));
	} elseif(count($resourceKeys) == 1){
		//error check, I only want the add and login commands with this api
		if($resourceKeys[0] != "add" && $resourceKeys[0] != "login"){
			header("Content-type: application/json");
			print(json_encode("Bad Request! '" . $resourceKeys[0] . "' is not a valid resource!"));
		} //handle user
		else if($resourceKeys[0] == "add"){
			//Convert to JSON and transmit
			header("Content-type: text/plain");
			//print "It's a post!!";
			$postData = file_get_contents("php://input");
			$user = json_decode($postData, true);
			
			//check if empty
			if(empty($user))
				die("User contains no data!");

			//check to see if username is available
			if(!login_available($user['username']))
			{
				die("Username already taken!");
			}
			//insert into database
			try{
				$DBH = openDBConnection();
				$DBH->beginTransaction();
				$stmt = $DBH->prepare("INSERT INTO user (name, username, password, role) VALUES(?,?,?,?);");
				$stmt->bindValue(1, $user["name"]);
				$stmt->bindValue(2, $user["username"]);
				$stmt->bindValue(3, $user["password"]);
				$stmt->bindValue(4, $user["role"]);
				$stmt->execute();
				$DBH->commit();
				//return success message
				print(json_encode("User inserted successfully!"));

			}catch(PDOException $e) {
				die("Problem inserting into DB!");
				reportDBError($e);
			}
			//handle login authentication
		} else if ($resourceKeys[0] == "login") {
			//set up things
			header("Content-type: text/plain");
			$postData = file_get_contents("php://input");
			$user = json_decode($postData, true);
			//check if empty
			if(empty($user))
				die("No credentials provided!");
			//check to see if user is in DB
			try{
				$DBH = openDBConnection();
				$stmt = $DBH->prepare("SELECT password FROM user WHERE username =  ?;");
				$stmt->bindValue(1, $user["username"]);
				$stmt->execute();

				//compare the 2 passwords
				//Note: this in no way attempts to be secure! This is a terrible implementation
				//for production code, but probably is ok for this app.
				if($row  = $stmt-> fetch()){
					$booleanMatch = $row["password"] == $user["password"];
					if($booleanMatch)
						print(json_encode("User successfully logged in!"));
					else
						print(json_encode("Invalid password!"));

				} //else the username doesn't exist!


			}catch(PDOException $e) {
				die("authentication error!");
				reportDBError($e);
			}

		}

			

	}else { //if there is more than 1 parameter in URL
		print(json_encode("Bad Request! Too many key words in URL. "));
	}


} else if($_SERVER['REQUEST_METHOD'] == "PUT") {

} else if($_SERVER['REQUEST_METHOD'] == "DELETE") {

}

// Logs and reports a database error
function reportDBError ($exception) {
	$file = fopen("application/log.txt", "a"); 
	fwrite($file, date(DATE_RSS));
	fwrite($file, "\n");
	fwrite($file, $exception->getMessage());
	fwrite($file, $exception->getTraceAsString());
	fwrite($file, "\n");
	fwrite($file, "\n");
	fclose($file);
	//require "views/error.php";
	exit();
}


/**
 *Checks to see if a login_id or name is available
 *@param
 * -login_id: a string
 *@return boolean
 * -return true if available, false if not
 */
function login_available($username)
{
	try {
		$DBH = openDBConnection();
		//check if loginName already exists
		$stmt = $DBH->prepare("SELECT COUNT(username) from user where username=?");
		$stmt->bindValue(1,$username);
		$stmt->execute();
		$row = $stmt->fetch();

		//if the user user exists
		if($row['COUNT(username)'] > 0)
			return false;
		else
		    return true; 				
	} catch (PDOException $e) { }
}
