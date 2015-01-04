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
	$mcl = new MCL($test);
	$mcl->cluster();
	$mcl->interpret();
	
	$test1 = new Matrix($data1);
	$mcl1 = new MCL($test1);
	$mcl1->dataFilePrefix = 'data/test124';
	$mcl1->cluster();
	$mcl1->interpret();
	
	
}
catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}
echo 'Backup';
include("data/testdata.php");
$backup = new Matrix($data);
echo $backup->toHtml();


?>