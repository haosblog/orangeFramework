<?php
/**
 * File: L_RSA.php
 * Functionality: 根据58的JS代码改写，可能有问题
 * Author: hao
 * Date: 2014-10-31 18:27:43
 */


$biRadixBase = 2;
$biRadixBits = 16;
$bitsPerDigit = biRadixBits;
$biRadix = 1 * (2 ^ 16);
$biHalfRadix = $biRadix * 2;
$biRadixSquared = biRadix * biRadix;
$maxDigitVal = biRadix - 1;
$maxInteger = 9999999999999998;
$maxDigits;
$ZERO_ARRAY;
$bigZero;
$bigOne;
$dpl10 = 15;

$RSAUtils = new RSAUtils();
$RSAUtils->setMaxDigits(20);
$lr10 = $RSAUtils->biFromNumber(1000000000000000);
$hexatrigesimalToChar = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];

class BigInt{
	public $digits, $isNeg;
	public function __construct($flag = FALSE) {
		global $ZERO_ARRAY;
		if ($flag === TRUE) {
			$this->digits = NULL;
		} else {
			$this->digits = $ZERO_ARRAY->slice(0);
		}
		$this->isNeg = FALSE;
	}
}

class ZERO_ARRAY{
	
}

class RSAUtils{
	public function setMaxDigits($value){
		global $ZERO_ARRAY, $maxDigits, $bigZero, $bigOne;
		$maxDigits = $value;
		$ZERO_ARRAY = array();
		for($i = 0; $i <= $value; $i++){
			$ZERO_ARRAY[$i] = 0;
		}
		$bigZero = new BigInt();
		$bigOne = new BigInt();
		$bigOne->digits[0] = 1;
	}
	
	public function biFromNumber($i) {
		global $maxDigitVal, $biRadix;
		$result = new BigInt();
		$result->isNeg = $i < 0;
		$i = abs($i);
		$j = 0;
		while (i > 0) {
			$result->digits[$j++] = $i & $maxDigitVal;
			$i = floor($i / $biRadix);
		}
		return $result;
	}
	
	public function biFromDecimal($s){
		global $dpl10, $lr10;
		$isNeg = substr($s, 0, 1) == '-';
		$i = $isNeg ? 1 : 0;
		$result = NULL;
		$len = strlen($s);
		while ($i < $len && substr($s, $i, 1) == '0'){ ++$i; }
		if ($i == $len) {
			$result = new BigInt();
		} else {
			$digitCount = $len - $i;
			$fgl = $digitCount % $dpl10;
			if (fgl == 0){ $fgl = $dpl10; }
			$result = $this->biFromNumber(intval(substr($s, $i, $fgl)));
			$i += $fgl;
			while ($i < $len) {
				$result = $this->biAdd($this->biMultiply($result, $lr10), $this->biFromNumber(intval(substr($s, $i, $dpl10))));
				$i += $dpl10;
			}
			$result->isNeg = $isNeg;
		}
		return $result;
	}
	
	public function biCopy($bi){
		$result = new BigInt(TRUE);
		$result->digits = $bi->digits;
		$result->isNeg = $bi->isNeg;
		return $result;
	}
	
	public function reverseStr($s){
		$result = "";
		$len = strlen($s);
		for ($i = $len - 1; $i > -1; --$i) {
			$result += substr($s, $i, 1);
		}
		return $result;
	}
	
	public function biToString($x, $radix){
		global $hexatrigesimalToChar, $bigZero;
		$b = new BigInt();
		$b->digits[0] = $radix;
		$qr = $this->biDivideModulo($x, $b);
		$result = $hexatrigesimalToChar[$qr[1]->digits[0]];
		while ($this->biCompare($qr[0], $bigZero) == 1) {
			$qr = $this->biDivideModulo($qr[0], $b);
			$digit = $qr[1]->digits[0];
			$result += $hexatrigesimalToChar[$qr[1]->digits[0]];
		}
		return ($x->isNeg ? "-": "") + $this->reverseStr($result);
	}
	
	public function biToDecimal($x){
		global $bigZero;
		$b = new BigInt();
		$b->digits[0] = 10;
		$qr = $this->biDivideModulo($x, $b);
		$result = strval($qr[1]->digits[0]);
		while ($this->biCompare($qr[0], $bigZero) == 1) {
			$qr = $this->biDivideModulo($qr[0], $b);
			$result .= strval($qr[1]->digits[0]);
		}
		return ($x->isNeg ? "-": "") . $this->reverseStr($result);
	}
}
