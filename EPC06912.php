<?php 
// Creates payload for QR-Code according to EPC guideline EPC069-12
// Repository: https://github.com/MarvinLudwig/EPC069-12
// Licence: Apache 2.0
// Version: 1.0

class EPC06912 {

	public static $error;

	public static function create ($name,$bic,$iban,$amount,$text,$encoding_str = null){
	
		self::$error = "";
		$error = "";
		$cutEpc = "";
		
		if ($encoding_str == null){
			$encoding_str = mb_detect_encoding($bic.$name.$iban.$amount.$text,mb_detect_order(),true);
			if ($encoding_str != "UTF-8") $encoding_str = "ISO-8859-1";
		}
		$encodings = array("UTF-8" => 1,"ISO-8859-1" => 2,"ISO-8859-2" => 3,"ISO-8859-4" => 4,
							"ISO-8859-5" => 5,"ISO-8859-7" => 6,"ISO-8859-10" => 7,"ISO-8859-15" => 8);
		$encoding_epc = $encodings[$encoding_str];
		
		// remove whitespace
		$bic = str_replace(" ", "", $bic);
		$iban = str_replace(" ", "", $iban);
		
		// check values (make sure that BIC and IBAN are valid, here we check only for length)
		if ($encoding_epc == "") $error .= "Character encoding $encoding_str is not supported.\r\n"; 
		if (!is_numeric($amount)) $error .= "Amount is not a valid number.\r\n";
		else {
			$amount = (float)$amount;
			$amount_arr = explode(".",$amount);
			if (count($amount_arr) == 2) {
				$decimals = $amount_arr[1];
				if (strlen($decimals) > 2) $error .= "Amount must not have more than 2 decimals.\r\n";;
			}
		}
		if (mb_strlen($name,$encoding_str) > 70) $error .= "Name has more than 70 characters.\r\n";
		if (mb_strlen($bic,$encoding_str) > 11) $error .= "BIC has more than 11 characters.\r\n";
		if (mb_strlen($iban,$encoding_str) > 34) $error .= "IBAN has more than 34 characters.\r\n";
		if (mb_strlen($amount,$encoding_str) > 12) $error .= "Amount has more than 12 characters.\r\n";
		if (mb_strlen($text,$encoding_str) > 140) $error .= "Reference text has more than 140 characters.\r\n";
		if ($error != "") {
			self::$error = array ("code" => 1, "message" => $error);
			return false;
		}
		
		// create string
		$epc = "";
		$epc .=  "BCD"."\n"; // Service Tag - BCD (currently the only possible value)
		$epc .=  "001"."\n"; // Version
		$epc .=  $encoding_epc."\n"; 
		$epc .=  "SCT"."\n"; // Identification - SCT: SEPA Credit Transfer (currently the only possible value)
		$epc .=  $bic."\n"; 
		$epc .=  $name."\n"; 
		$epc .=  $iban."\n";
		$epc .=  "EUR".$amount."\n"; 
		$epc .=  "OTHR"."\n"; // Default purpose - OTHR (TODO: implement others)
		$epc .=  $text."\n";
		
		// check max bytes (331)
		$strBytes = strlen($epc);
		if ($strBytes > 331){
			$textBytes = strlen($text);
			$excessBytes = $strBytes-331;
			
			// Check if we could cut the text to comply with the bytes limit
			if ($textBytes >= $excessBytes){ 
				$cutText = mb_strcut($text,0,$textBytes-$excessBytes-1,$encoding_str);
				$error .= "Total payload is more than 331 bytes ($strBytes). The reference text has $textBytes bytes.";
			}
			
			if ($error != "") {
				self::$error = array ("code" => 2, "message" => $error, "details" => $cutText);
				return false;
			}
		}
		return $epc;
	}
}
?>
