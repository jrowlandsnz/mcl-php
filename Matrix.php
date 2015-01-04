<?php

/**
 * Represents an array as an array of arrays. Each sub-array represents a row
 */
class Matrix {
	
	var $data;
	var $isSquare = false;
	var $rowCount;
	var $colCount;
	var $zeroValue = 0.0001; //value below which to set an element to 0
	var $highValueCount;
	var $matrixKeyArray = null;
	
	//use these to reduce the amount of looping by excluding elements already set to 0
	var $rowValuesStart = array();
	var $rowValuesEnd = array();
	var $pruneZeros = false;
	
	function Matrix($data = null) {
		if($data != null) {
			$this->setData($data);
		}
	}
	
	
	function setData($data) {
		//TODO: some validation to make sure matrix is valid i.i all rows same length
		
		$isValid = true;
		$rowSize = sizeof($data[0]);
		$rowCount = sizeof($data);
		$i = 1; //start at second row
		while($isValid && $i < $rowCount) {
				
			if(sizeof($data[$i]) != $rowSize) {
				$isValid = false;
				throw new Exception("Matrix rows are not same length. Was ".sizeof($data[$i])." expecting ".$rowSize);
			}
			$i++;	
		}
		
		$this->data = $data;
		$this->rowCount = sizeof($data);
		$this->colCount = sizeof($data[0]);
		if(sizeof($data) == sizeof($data[0])) {
			$this->isSquare = true;
		}
		else {
			$this->isSquare = false;
		}
	}
	
	//adds self loops to a matrix of paths in a graph
	function addSelfLoop() {
		if($this->isSquare) {
			for($i = 1; $i <= $this->rowCount; $i++) {
				$this->setElement($i,$i,1);	
			}
		}
		else {
			throw new Exception("Matrix is not square");
		}
	}
	
	//this turns the vertex weights into the probability you will 
	//travel along that vertex from the node
	//it is travel from colX -> rowX
	function normalise() {
		$this->highValueCount = 0;
		//this operation is done by column
		for($currentCol = 1; $currentCol <= $this->colCount; $currentCol++) {
			$columnTotal = 0;
			for($currentRow = 1; $currentRow <= $this->rowCount; $currentRow++) {
				$columnTotal += $this->getElement($currentRow, $currentCol);
			}

			//now divide each value by the total
			for($currentRow = 1; $currentRow <= $this->rowCount; $currentRow++) {
				if($columnTotal > 0) {	
					$newValue = $this->getElement($currentRow, $currentCol)/$columnTotal;	
				}
				else {
					$newValue = 0;
				}
				$this->setElement($currentRow,$currentCol,$newValue);
				if($newValue > 0.9) {
					$this->highValueCount++;
				}
			}
		}		
	}
	
	//multiply matrix by itself $power times
	function getNthPower($power) {
		//TODO: validate this matrix is square 
		
		//store a copy for future multiplications
		$originalMatrix = clone $this;
		for($i = 1; $i < $power; $i++) {
			echo "Multiply $i";
			if($this->pruneZeros) {
				$this->multiplyWithPruning($originalMatrix);
			}
			else {
				$this->multiply($originalMatrix);
			}
			
		}
	}
	
	//multiply this matrix by $matrix
	function multiply($matrix) {
		//TODO: validate that the multiplication is valid
		//TODO: modify to work for non-square matrix
		$oldMatrix = clone $this;
		for($row = 1; $row <= $this->rowCount; $row++) {
			//echo "Multiply Row $row<br/>\n";
			for($col = 1; $col <= $this->colCount; $col++) {
				echo "\tMultiply Row $row Col $col<br/>\n";	
				//echo memory_get_usage()."\n";	
				$newValue = 0;
				
				//echo memory_get_usage()."\n";
				//multiply across $row and down $col
				for($i = 1; $i <= $this->colCount; $i++) {	
					$newValue += $oldMatrix->getElement($row, $i) * $matrix->getElement($i, $col);	
				}
				///echo memory_get_usage()."\n";
				$this->setElement($row, $col, $newValue);
				//echo memory_get_usage()."\n";				
			}
		}
	}
	
	//multiply this matrix by $matrix
	function multiplyWithPruning($matrix) {
		//TODO: validate that the multiplication is valid
		//TODO: modify to work for non-square matrix
		
		
		if(sizeof($this->rowValuesStart) == 0) {
			//setup the pruning
			echo "<p>Setting up pruning</p>";
		}
		
		$oldMatrix = clone $this;
		for($row = 1; $row <= $this->rowCount; $row++) {
			//echo "Multiply Row $row<br/>\n";
			for($col = 1; $col <= $this->colCount; $col++) {
				if($this->getElement($row,$col) > 0) {		
						
					$newValue = 0;
					
					echo "\tMultiply Row with Pruning $row Col $col<br/>\n";
					//multiply across $row and down $col
					for($i = 1; $i <= $this->colCount; $i++) {	
						$newValue += $oldMatrix->getElement($row, $i) * $matrix->getElement($i, $col);	
					}
					$this->setElement($row, $col, $newValue);		
				}		
			}
		}
	}
	
	function inflate($value) {
		for($row = 1; $row <= $this->rowCount; $row++) {
			for($col = 1; $col <= $this->colCount; $col++) {
				$currentValue = $this->getElement($row, $col);
				$newValue = pow($currentValue, $value);
				$this->setElement($row, $col, $newValue);
			}
		}
	}
	
	
	//sum all of the elements
	function sum() {
		$sum = 0;
		foreach($this->data as $row) {
			$sum += array_sum($row);
		}
		return $sum;
	}	
	
	//returns true if every column has fewer than or equal to $value non-zero entroes
	function allColsNumberValues($value) {
		for($col = 1; $col <= $this->colCount; $col++) {
			$nonZeroEntries = 0;
			for($row = 1; $row <= $this->rowCount; $row++) {
				if($this->getElement($row, $col) > 0) {
					$nonZeroEntries++;
					if($nonZeroEntries > $value) return false;
				}
			}
		}
		return true;				
					
	}
	
	
	
	function setElement($rowIndex, $columnIndex, $value) {
		//TODO: validate that the element exists first	
		//echo memory_get_usage()."\n";
		if($value < $this->zeroValue) {
			$value = 0; //done to prevent lots of loops when values get very small and we are multiplying
		}
		//unset($this->data[$rowIndex - 1][$columnIndex - 1]);  //dont do this as it will move elements around in array 
		$this->data[$rowIndex - 1][$columnIndex - 1] = $value;
		
		//echo memory_get_usage()."\n";
	}
	
	
	function getElement($rowIndex, $columnIndex) {
		return  $this->data[$rowIndex-1][$columnIndex-1];	
	}
	
	function toHTML() {
		//TODO: make sure data is set	
		if($this->matrixKeyArray == null) {	
			$html = "<table cellpadding=\"2\">\n";
	        foreach($this->data as $row) {
	        	$html .= "\t<tr>";
				foreach($row as $col) {
					$html .= "\t<td>".substr($col,0,4)."</td>\n";
				}
				
				$html .= "</tr>\n";
	        }
	        
	        $html = $html."\n</table>\n";
		}
		else {
			$html = "<table border=1 cellpadding=\"2\">\n";
	        $html .= "<tr><th>&nbsp;</th>";
	        foreach($this->matrixKeyArray as $key) {
	        	$html .= "\t<th>".$key."</th>\n";
			}
			$html .= "</tr>";
			$i = 0;
	        foreach($this->data as $row) {
	        	$html .= "\t<tr>";
				$html .= "<th>".$this->matrixKeyArray[$i]."</th>";
				foreach($row as $col) {
					$html .= "\t<td>".substr($col,0,4)."</td>\n";
				}
				$i++;
				$html .= "</tr>\n";
	        }
	        
	        $html = $html."\n</table>\n";
			
			
		}
        return $html;
	}
	
	function toPHP() {
		$string = '<?php'."\n".'$data = array();'."\n";
		foreach($this->data as $row) {
        	$string .= '$data[] = array(';
			foreach($row as $col) {
				$string .= $col.',';
			}
			$string = substr($string, 0, strlen($string) - 1); //remove trailing ,
			$string .= ");\n";
        }
		$string .= '?>';
		return $string;
	}
	
}


?>