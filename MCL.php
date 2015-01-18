<?php
/**
 * Class that holds rules to perfrom MCL on a supplied Matrix
 */
class MCL {
	
	var $powerValue = 2;
	var $inflationValue = 2;
	var $matrix;
	var $matrixKeyArray;  //set this to map row ids from db to matrix rows
	var $clusters;
	
	var $dataFilePrefix = '';  //set this if you would like the matrix to be written to a file after each iteration
								//usefull for large matrices that will take a while to run
	var $skipSetup = false; //used to skip initial setting of self loops and normalisation
							//when loading matrix from a backup file
							
	var $minInterpretationValue = 0.2; //minimum elemnet value in final matrix that mean row and column
										//are in same cluster						
	
	function MCL($matrix) {
		$this->matrix = $matrix;
	}
	
	function cluster($prune) {
		
		//save $matrixKey to file if it exists
		if(strlen($this->dataFilePrefix) > 0 && is_array($this->matrixKeyArray) && sizeof($this->matrixKeyArray) > 0) 
		{
			file_put_contents($this->dataFilePrefix."key.php", $this->matrixToPHP('key', $this->matrixKeyArray));	
			file_put_contents($this->dataFilePrefix."data.php", $this->matrix->toPHP()); //always will have the latest version
			file_put_contents($this->dataFilePrefix."data_start.php", $this->matrix->toPHP()); //starting mattrix
			
		}
		
		//print_r($this->matrix);
		$this->matrix->pruneZeros = $prune;
		//echo '<p>Starting Matrix</p>';
		//echo $this->matrix->toHTML();
		if(!$this->skipSetup) {
			$this->matrix->addSelfLoop();
			//echo '<p>Add Self Loop</p>';
			//echo $this->matrix->toHTML();
			if(strlen($this->dataFilePrefix) > 0 && is_array($this->matrixKeyArray) && sizeof($this->matrixKeyArray) > 0) 
			{
				//file_put_contents($this->dataFilePrefix."key.php", $this->matrixToPHP('key', $this->matrixKeyArray));	
				//file_put_contents($this->dataFilePrefix."data.php", $this->matrix->toPHP()); //always will have the latest version
				file_put_contents($this->dataFilePrefix."data_start_self_loop.php", $this->matrix->toPHP()); //starting mattrix
				
			}
			
			$this->matrix->normalise();
			//echo '<p>Normalise</p>';
			//echo $this->matrix->toHTML();
			
			if(strlen($this->dataFilePrefix) > 0 && is_array($this->matrixKeyArray) && sizeof($this->matrixKeyArray) > 0) 
			{
				//file_put_contents($this->dataFilePrefix."key.php", $this->matrixToPHP('key', $this->matrixKeyArray));	
				file_put_contents($this->dataFilePrefix."data.php", $this->matrix->toPHP()); //always will have the latest version
				file_put_contents($this->dataFilePrefix."data_start_post_setup.php", $this->matrix->toPHP()); //starting mattrix
				
			}
		}
		
		$lastHighValueCount = -1;  //so we don't get a flase positive on first loop
		$lastAllColsNumberValues = false;
		for($i = 0; $i < 20; $i++) {
			if($lastAllColsNumberValues && $lastHighValueCount > 1 && $lastHighValueCount == $this->matrix->highValueCount) {
				break;
			}	 
			else {
				$lastAllColsNumberValues = $this->matrix->allColsNumberValues(3);
				$lastHighValueCount = $this->matrix->highValueCount;
			}
			//echo "<h2>Loop $i</h2>";
			
			$this->matrix->getNthPower($this->powerValue);
			//echo '<p>Square</p>';
			//echo $this->matrix->toHTML();
			
			$this->matrix->inflate($this->inflationValue);
			//echo '<p>Inflate</p>';
			//echo $this->matrix->toHTML();
			
			$this->matrix->normalise();
			//echo '<p>Normalise</p>';
			//echo $this->matrix->toHTML();
			
			if(strlen($this->dataFilePrefix) > 0) {
				//write current state to file so we can pick up later if needed
				file_put_contents($this->dataFilePrefix."data.php", $this->matrix->toPHP()); //always will have the latest
				file_put_contents($this->dataFilePrefix."data_backup_$i.php", $this->matrix->toPHP()); 
			}
			
			//echo "<p>High Value Count: ".$this->matrix->highValueCount."</p>";
			
			//echo "<p>Is Complete: ".$this->matrix->allColsNumberValues(3)."</p>";
			
		}
		$this->matrix->normalise();
		//echo '<p>Normalise</p>';
		//echo $this->matrix->toHTML();
		
	}

	//Interpret the clusters
	function interpret() {	
		$numberClusters = 0;
		$elementClusterValues = array(); //track which cluster number each element ends up in
		
		for($i = 0; $i < $this->matrix->rowCount; $i++) {
			$elementClusterValues[$i] = 0;
		}
		
		//loop through each row and add columns with value > 0.5 to same cluster as row element
		//do this in a seperate loop so we know the values have all been initialised
		//assumes each element will only be in one cluster
		for($i = 0; $i < $this->matrix->rowCount; $i++) {
			if($elementClusterValues[$i] == 0) {
				//element hasn't been added to cluster
				
				//create a cluster
				$numberClusters++;
				$elementClusterValues[$i] = $numberClusters;  //note this may get overrriden later if there
															  //are no positive values in this row so element
															  //isn't an 'attractor' and is in another cluster
				
				//add any positive elemtents in this row to the cluster
				for($j = 0; $j < $this->matrix->colCount; $j++) {
					if($this->matrix->getElement($i + 1, $j + 1) > $this->minInterpretationValue) {
						$elementClusterValues[$j] = $numberClusters;
					}
				}
			}
		}
		
		//now loop through cluster values array and slipt based on cluster number
		$this->clusters = array();
		foreach($elementClusterValues as $rowNumber => $clusterNumber) {
			if(!key_exists($clusterNumber, $this->clusters)) {
				$this->clusters[$clusterNumber] = array();	
				
			}
			$this->clusters[$clusterNumber][] = $rowNumber;
		}
		
		//rebase cluster array
		$this->clusters = array_values($this->clusters);
		
		//save cluster values to file
		if(strlen($this->dataFilePrefix) > 0) {
			file_put_contents($this->dataFilePrefix."cluster.php", $this->matrixToPHP('cluster', $this->clusters));
			if(is_array($this->matrixKeyArray) && sizeof($this->matrixKeyArray) > 0) {
				file_put_contents($this->dataFilePrefix."clusterWithKey.php", $this->matrixToPHP('clusterWithKey', $this->getClusterValuesToKeys()));
			}
		}
		
		//return the cluster
		if(is_array($this->matrixKeyArray) && sizeof($this->matrixKeyArray) > 0) {
			return $this->getClusterValuesToKeys();
		}
		else {
			return $this->clusters;
		}
	}

	function getClusterValuesToKeys() {
		$clusterWithKeys = array();
		
		foreach($this->clusters as $clusterNumber => $clusterRows) {
			$clusterWithKeys[$clusterNumber] = array();
			foreach($clusterRows as $row) {
				$clusterWithKeys[$clusterNumber][] = $this->matrixKeyArray[$row];
			}
		}
		
		return $clusterWithKeys;
	}
	
	function matrixToPHP($name, $array) {
		$string = '<?php'."\n".'$'.$name.' = array();'."\n";
		$i = 0;
		foreach($array as $key => $row) {
			if(is_array($row)) {
	        	$string .= '$'.$name.'['.$key.'] = array(';
				foreach($row as $colKey => $col) {;
					if(is_string($col)) {
						$string .= '"'.$col.'",';
					}
					else {
						$string .= $col.',';
					}
				}
				$string = substr($string, 0, strlen($string) - 1); //remove trailing ,
				$string .= ");\n";
			}
			else if(is_string($row)) {
				$string .= '$'.$name.'['.$key.'] = "'.$row.'";'."\n";
			}
			else {
				$string .= '$'.$name.'['.$i.'] ='.$row.';'."\n";	
			}
			$i++;
        }
		$string .= '?>';
		return $string;
	}
	
}




?>