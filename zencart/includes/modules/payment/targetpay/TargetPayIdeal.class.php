<?php
/**
 * TargetPay Payment Module for osCommerce
 *
 * @copyright Copyright 2013-2014 Yellow Melon
 * @copyright Portions Copyright 2013 Paul Mathot
 * @copyright Portions Copyright 2003 osCommerce
 * @license see LICENSE.TXT
 */


require_once ( 'TargetPayClass.class.php' );

class TargetPayIdealOld extends TargetPayClass {

	var $error;
	var $errorcode;

	/**
	 * @desc constructor class
	 * @param integer $intRtlo
	 */
	public function __construct( $intRtlo ) {
    
		/**
		 * call parent constructor
		 */
		parent::__construct( $intRtlo );
      
	}
  
	/**
	 * @desc start payment
	 * @return array ( trxid, idealReturnUrl )
	 */
    public function startPayment () 
    {
		# Build parameter string
		//$aParameters = $this->getBaseRequest();
		$aParameters = array();
		$aParameters['rtlo'] = $this->intRtlo;
		$aParameters['bank'] = $this->idealIssuer;
		$aParameters['description'] = $this->strDescription;
		$aParameters['currency'] = $this->strCurrency;
		$aParameters['amount'] =  $this->idealAmount;
		$aParameters['language'] = $this->strLanguage;
		$aParameters['returnurl'] = $this->strReturnUrl;
		$aParameters['reporturl'] = $this->strReportUrl;

		# do request
		$strResponse = $this->getResponse( $aParameters, 'https://www.targetpay.com/ideal/start?');
		$aResponse = explode('|', $strResponse );

		# Bad response
		if (!isset ($aResponse[1]) ) 
        {
          	$this->setError($this->getErrorDescription($aResponse[0]));	
            return false;
        }

        $iTrxID = explode ( ' ', $aResponse[0] );
        return array ( $iTrxID[1], $aResponse[1] );
  	}

  	/**
  	 * @desc get all banks (various methods
  	 * @return array $banks_array
  	 */
	public function getBanks()
	{
		
		
		$banks_array = array();
		$i = new stdClass();
					$i->issuerID ="test";
					$i->issuerName = "asdfsadfsa";
					$i->issuerList = "Short";
					$banks_array[]=$i;
return $banks_array;


		//~ $file = file_get_contents("https://www.targetpay.com/ideal/issuers-nl.js", "r");
		//~ $file = str_ireplace("d.writeln('');","",$file);
		//~ $file = str_ireplace("d=document;","",$file);
		//~ $aFileparts = explode("d.writeln",$file);
		//~ if(count($aFileparts) > 0)
		//~ {
			//~ foreach($aFileparts as $sFilepart)
			//~ {
				//~ $iBankValue = preg_replace("/[^0-9]/", "", strtolower($sFilepart));
				//~ $sBankName = trim(strip_tags(str_replace(array("('","')",";"),"",$sFilepart)));
				//~ 
				//~ if($iBankValue > 0 && $iBankValue != "")
				//~ {
					//~ echo 'hier<br/>';
					//~ $i = new stdClass();
					//~ $i->issuerID = $iBankValue;
					//~ $i->issuerName = $sBankName;
					//~ $i->issuerList = "Short";
					//~ $banks_array[]=$i;
				//~ }
				//~ 
			//~ }
		//~ }
		//~ else
		//~ {
			//~ echo 'daar<br/>';
			//~ return $this->getStaticBanks();
		//~ }
		//~ 
		//~ if(count($banks_array) > 0)
		//~ {
			//~ return $banks_array;
		//~ }
		//~ else
		//~ {
			//~ return $this->getStaticBanks();
		//~ }
			
		
	}
  
  
  	/**
  	 * @desc Validate the payment now by trxId
  	 * @param int $intTrxId
  	 * @param int $iOnce
  	 * @param int $iTest
  	 * @return bool true
  	 * @return string error mesage
  	 */
   	public function validatePayment ( $intTrxId, $iOnce = 1, $iTest = 0 )
   	{
		# Build parameter string
		$aParameters = array();
		$aParameters['rtlo'] = $this->intRtlo;
		$aParameters['trxid'] = $intTrxId;
		$aParameters['once'] = $iOnce;
		$aParameters['test'] = $iTest; 
           
		# do request
		$strResponse = $this->getResponse ( $aParameters , 'https://www.targetpay.com/ideal/check?');
		$aResponse = explode('|', $strResponse );

		# Bad response
		if (  $aResponse[0] != '000000 OK' ) {
			$this->setError($aResponse[0]);
			return $aResponse[0];
		}
		return true;   
   	}

	/**
 	* @desc set the error code
 	* @param string $error
 	*/
	private function setError($error)
	{
  		$this->error = $error;
	}

	/**
 	* @desc set the error code
 	* @param string $error
 	*/
	private function setErrorCode($errorcode)
	{
  		$this->errorcode = $errorcode;
	}

	/**
	 * @desc get the error string
	 * @return string $this->error
	 */
	public function getError()
  	{
		return $this->error;
	}

	/**
	 * @desc get the error code
	 * @return string $this->errorcode
	 */
	public function getErrorCode()
  	{
		return $this->errorcode;
	}

	/**
 	* @Desc set ideal return url
 	* @param string $strReturnUrl
 	* @return string $this
 	*/
  	public function setIdealReturnUrl ( $strReturnUrl ) 
  	{
    	$this->strReturnUrl = $strReturnUrl;  
     	return $this;   
  	}
  
	/**
	 * @desc set ideal return url
	 * @param $strReportUrl
	 * @return string $this
	 */
  	public function setIdealReportUrl ( $strReportUrl ) 
  	{
    	$this->strReportUrl = $strReportUrl;  
     	return $this;   
  	}
  
  	/**
  	 * @desc set ideal description for transaction
  	 * @param unknown_type $strDescription
  	 * @return string $this
  	 */
	public function setIdealDescription ( $strDescription ) 
	{
		$this->strDescription = $strDescription;  
		return $this;   
	}

	/**
	 * @desc set ideal languagecode for transaction
	 * @param $strLanguage
	 * @return string $this
	 */
  	public function setIdealLanguage ( $strLanguage ) 
  	{
   		$this->strLanguage = $strLanguage;  
     	return $this;   
  	}

  	/**
  	 * @desc set ideal description for transaction
  	 * @param string $strCurrency
  	 * @return string $this
  	 */
  	public function setIdealCurrency ( $strCurrency ) 
  	{
    	$this->strCurrency = $strCurrency;  
     	return $this;   
  	}
  
  	/**
  	 * @desc set ideal amount
  	 * @param int $intIdealAmount
  	 * @return string $this
  	 */
	public function setIdealAmount ( $intIdealAmount ) 
  	{
		# Is this a valid ideal amount?
		if ( is_numeric ( $intIdealAmount ) && $intIdealAmount > 0 ) {
			$this->idealAmount = $intIdealAmount;    
		}
		else {
			$this->setError(MODULE_PAYMENT_TARGETPAY_ERROR_AMOUNT_TO_LOW);
			return false;
		}
       	return $this;
  	}

  	/**
  	 * @desc set ideal issuer
  	 * @param int $intIdealIssuer
  	 * @return string $this
  	 */
	public function setIdealissuer ( $intIdealIssuer )
	{
		$this->idealIssuer = $intIdealIssuer;
		return $this;
	}

	/**
	 * @desc get nice error description
	 * @param string $sErrorCode
	 * @return string description
	 */
	public function getErrorDescription($sErrorCode)
	{
  		$sErrorCodePart = explode(" ",$sErrorCode);
  		$this->setErrorCode($sErrorCodePart[0]);
  		switch ($sErrorCodePart[0])
    	{
     		case "TP0001":
     			return "Geen layoutcode opgegeven";
            	break;
            case "TP0002":
     			return "Bedrag te laag (minimaal 0,84 euro)";
            	break;
            case "TP0003":
     			return "Bedrag te hoog (maximaal 10.000 euro)";
            	break;
     		case "TP0004":
     			return "Geen of ongeldige return URL meegegeven";
            	break;
            case "TP0005":
     			return "Geen bank ID meegegeven";
            	break;
            case "TP0006":
     			return "Geen omschrijving meegegeven";
            	break;
            case "IX1000":
     			return "Ontvangen XML niet well-formed";
            	break;
            case "IX1100":
     			return "Ontvangen XML niet valide";
            	break;
            case "IX1200":
     			return "Encoding type geen UTF-8";
            	break;
            case "IX1300":
     			return "Versienummer niet (meer) ondersteund";
            	break;
            case "IX1400":
     			return "Onbekend bericht";
            	break;
            case "IX1500":
     			return "Verplichte hoofdentiteit ontbreekt in bericht";
            	break;
            case "SO1000":
     			return "Storing in systeem";
            	break;
            case "SO1200":
     			return "Systeem te druk. Probeer later nogmaals";
            	break;
            case "SO1400":
     			return "Onbeschikbaar door onderhoudswerkzaamheden";
            	break;
            case "IX1400":
     			return "Onbekend bericht";
            	break;
            case "SE2000":
     			return "Authenticatiefout";
            	break;
            case "SE2100":
     			return "Authenticatie methode niet ondersteund";
            	break;
            case "SE2700":
     			return "Ongeldige digitale handtekening";
            	break;
            case "BR1200":
     			return "Ongeldig versienummer";
            	break;
            case "BR1210":
     			return "Veld bevat niet toegestaan teken";
            	break;
            case "BR1220":
     			return "Veld te lang";
            	break;
            case "BR1230":
     			return "Veld te kort";
            	break;
            case "BR1240":
     			return "Waarde te hoog";
            	break;
            case "BR1250":
     			return "Waarde te laag";
            	break;
            case "BR1260":
     			return "Onbekende optie in lijst";
            	break;
            case "BR1270":
     			return "Ongeldige datum/tijd";
            	break;
            case "BR1280":
     			return "Ongeldige URL";
            	break;
            case "AP1100":
     			return "merchantID onbekend";
            	break;
            case "AP1200":
     			return "issuerID onbekend";
            	break;
            case "AP1300":
     			return "subID onbekend";
            	break;
            case "AP2600":
     			return "Transactie bestaat niet";
            	break;
            case "AP2620":
     			return "Transactie reeds aangeboden";
            	break;
            case "AP2700":
     			return "Bankrekeningnummer niet 11-proof";
            	break;
            case "AP2900":
     			return "Gekozen valuta niet ondersteund";
            	break;
 			case "AP2910":
     			return "Gekozen valuta niet ondersteund";
            	break;
            case "AP2920":
     			return "Expiratieperiode te groot (meer dan 1 uur)";
            	break;
      		default:
            	return $sErrorCodePart[1];
            break;
    	}
    	return $sErrorCodePart[1];
	}
  /**
  * @desc Get available issuers, and return array
  *
  * @return array
  */
    private function getStaticBanks()
    {
    	/**
    	 * @var $banks_array unknown_type
    	 *     
    	 * ABN AMRO Bank id: 0031
    	 * ASN Bank 0761 
    	 * Fortis Bank 0081
    	 * Friesland Bank 0091
    	 * ING Bank 0721
    	 * Rabobank 0021
    	 * SNS Bank 0751
    	 * SNS Regio Bank 0771
    	 * Tridios Bank 0511
    	 */
    	$banks_array = array();
    	$staticbanksarray = array('0031'=>'ABN AMRO Bank', '0761'=>'ASN Bank', '0081'=>'Fortis Bank', '0091'=>'Friesland Bank', '0721'=>'ING Bank', '0021'=>'Rabobank', '0751'=>'SNS Bank', '0771'=>'SNS Regio Bank', '0511'=>'Tridios Bank');
    	
    	//~ foreach($staticbanksarray as $iBankValue=>$sBankName)
    	//~ {
    		//~ $i = new stdClass();
    		//~ $i->issuerID = $iBankValue;
		    //~ $i->issuerName = $sBankName;
		    //~ $i->issuerList = "Short";
			//~ $banks_array[]=$i;
    	//~ }
    	
    	$i = new stdClass();
    		$i->issuerID = 'testblaat';
		    $i->issuerName = 'getstatic';
		    $i->issuerList = "Short";
			$banks_array[]=$i;
		return $banks_array;
    }
}
  
  
?>
