<?php
error_reporting(E_ALL);
	
include_once('Matrix.php');
include_once('MCL.php');
//var_dump(class_exists('Matrix', false));




/**
//example from http://www.cs.ucsb.edu/~xyan/classes/CS595D-2009winter/MCL_Presentation2.pdf
$data = array();
$data[] = array(0,1,1,1);
$data[] = array(1,0,0,1);
$data[] = array(1,0,0,0);
$data[] = array(1,1,0,0);
// */

$data = array();
$data[] = array(0,1,1,0,0,0);
$data[] = array(1,0,1,0,0,0);
$data[] = array(1,1,0,1,0,0);
$data[] = array(0,0,1,0,1,1);
$data[] = array(0,0,0,1,0,1);
$data[] = array(0,0,0,1,1,0);

$data1 = array();
$data1[] = array(0,1,1,1,0,0,0,0,0);
$data1[] = array(1,0,1,1,0,0,0,1,0);
$data1[] = array(1,1,0,1,0,0,0,0,0);
$data1[] = array(1,1,1,0,1,0,0,0,0);
$data1[] = array(0,0,0,1,0,1,1,1,0);
$data1[] = array(0,0,0,0,0,0,1,1,1);
$data1[] = array(0,0,0,0,1,1,0,1,1);
$data1[] = array(0,1,0,0,1,0,1,0,1);
$data1[] = array(0,0,0,0,0,1,1,1,0);

//TODO: move this into a class

//print_r($data);
try {
	$test = new Matrix($data);
	echo "<h2>Test Matrix 1</H2>";
	echo $test->toHTML();
	
	$mcl = new MCL($test);
	$mcl->cluster();
	$mcl->interpret();
	
	echo "<p>Clusters:</p><pre>";
	print_r($mcl->clusters);
	echo "</pre>";
	
	$test1 = new Matrix($data1);
	echo "<h2>Test Matrix 2</H2>";
	echo $test1->toHTML();
	
	$mcl1 = new MCL($test1);
	//uncommenting below line will save results to a php file
	//$mcl1->dataFilePrefix = 'data/test124';
	$mcl1->cluster();
	$mcl1->interpret();

	echo "<p>Clusters:</p><pre>";
	print_r($mcl1->clusters);
	echo "</pre>";
	
}
catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}

?>