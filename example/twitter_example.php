<?php
include_once('../Matrix.php');
include_once('../MCL.php');

//connect to database
$dbServer = 'localhost';
$dbUser = 'test_user';
$dbPassword = 'test_password';
$dbName = 'twitter';

$dbConn = mysqli_connect($dbServer, $dbUser, $dbPassword, $dbName);
if (mysqli_connect_errno()) {
    echo "Unable to connect to the database server at this time";
    exit();
}

$matrixKey = array();
$dbData = array();
$filePrefix = 'twitter_test_data'; //optional, used to output data files

$sql = "SELECT u1.screen_name as user_name, u2.screen_name as follows_user_name
			FROM `following` f
			INNER JOIN user u1 ON u1.user_id = f.user_id
			INNER JOIN user u2 ON u2.user_id = f.follows_user_id";

if($result = $dbConn->query($sql)) {

	while($row = $result->fetch_assoc()) {
		//get a list of all the keys that will be in the array, this will also give us the size of the 
		//array we need to create  
		$key1 = array_search("".$row['user_name'],$matrixKey,TRUE);
		if($key1 === false) {
			$matrixKey[] = "".$row['user_name'];
		}
		
		$key2 = array_search("".$row['follows_user_name'],$matrixKey,TRUE);
		if($key2 === false) {
			$matrixKey[] = "".$row['follows_user_name'];
		}
		
		$dbData[] = $row; //store the data so we don't need to query the db again
	}
	asort($matrixKey);
	
	//now create the data matrix and cycle through the data again
	//need to create a matrix of 0's that we will then fill in (not all intersections are in our data)
	$matrixData = array();
	for($i = 0;  $i < sizeof($matrixKey); $i++) {
			
		$rowOfZero = array();
		for($j = 0;  $j < sizeof($matrixKey); $j++) {		
			$rowOfZero[] = 0;
		}
		$matrixData[] = $rowOfZero;
	}
	
	
	try {
		$matrix = new Matrix($matrixData);
		
		//now populate with data from database
		foreach($dbData as $row) {
			$rowIndex = array_search("".$row['user_name'], $matrixKey, TRUE);
			if($rowIndex === FALSE) {
				throw new Exception("Matrix Key not present");
			} 
			
			$colIndex = array_search("".$row['follows_user_name'], $matrixKey, TRUE);
			if($colIndex === FALSE) {
				throw new Exception("Matrix Key not present");
			} 
			
			$rowIndex = $rowIndex + 1; //need to convert from array index to matrix index 
			$colIndex = $colIndex + 1; //need to convert from array index to matrix index 
			
			$matrix->setElement($rowIndex, $colIndex, 1);
		}
		
		$dbData = null;  //free up some memory
		
		$matrix->matrixKeyArray = $matrixKey;
		echo $matrix->toHTML();

		$mcl = new MCL($matrix);
		$mcl->dataFilePrefix = $filePrefix;
		$mcl->matrixKeyArray = $matrixKey;
		$mcl->inflationValue = 2;
		$mcl->powerValue = 2;
		$mcl->cluster(true);
		$clusters = $mcl->interpret();
		
		
		//print the clusters to the screen
		echo '<pre>';
		print_r($clusters);
		echo '</pre>';
	}
	catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
}
else {
	echo 'Error '.$db->error();
}
?>