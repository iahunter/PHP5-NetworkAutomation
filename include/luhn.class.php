<?php
/**
 * Luhn algorithm (a.k.a. modulus 10) is a simple formula used to validate variety of identification numbers.
 * It is not intended to be a cyrptographically secure hash function, it was designed to protect against accidental errors.
 * See http://en.wikipedia.org/wiki/Luhn_algorithm
 * 
 * @author Rolands Kusiņš
 * @version 0.2
 * @license GPL
 */
class Luhn
{
	private $sumTable = array(array(0,1,2,3,4,5,6,7,8,9),array(0,2,4,6,8,1,3,5,7,9));
	/**
	 * Calculate check digit according to Luhn's algorithm
	 * New method (suggested by H.Johnson), see http://www.phpclasses.org/discuss/package/8471/thread/1/
	 * 
	 * @param string $number
	 * @return integer
	 */
	public function calculate($number){
		$length = strlen($number);
		$sum = 0;
		$flip = 1;
		// Sum digits (last one is check digit, which is not in parameter)
		for($i=$length-1;$i>=0;--$i) $sum += $this->sumTable[$flip++ & 0x1][$number[$i]];
		// Multiply by 9
		$sum *= 9;
		// Last digit of sum is check digit
		return (int)substr($sum,-1,1);
	}
	
	/**
	 * Calculate check digit according to Luhn's algorithm
	 * This is an old method, tests show that this is little bit slower than new one
	 * 
	 * @param string $number
	 * @return integer
	 */
	public function calculateOld($number){
		$length = strlen($number);
		$sum = 0;
		$p = $length % 2;
		// Sum digits, where every second digit from right is doubled (last one is check digit, which is not in parameter)
		for($i=$length-1;$i>=0;--$i){
			$digit = $number[$i];
			// Every second digit is doubled
			if($i % 2 != $p){
				$digit *= 2;
				// If doubled value is 10 or more (for example 13), then add to sum each digit (i.e. 1 and 3)
				if($digit > 9){
					$sum += $digit[0];
					$sum += $digit[1];
				} else{
					$sum += $digit;
				}
			} else{
				$sum += $digit;
			}
		}
		// Multiply by 9
		$sum *= 9;
		// Last one is check digit
		return (int)substr($sum,-1,1);
	}
	
	/**
	 * Validate number against check digit
	 * 
	 * @param string $number
	 * @param integer $digit
	 * @return boolean
	 */
	public function validate($number,$digit){
		$calculated = $this->calculate($number);
		if($digit == $calculated) return true;
		else return false;
	}
}
?>
