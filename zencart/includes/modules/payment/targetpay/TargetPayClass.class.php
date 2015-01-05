<?php
/**
 * TargetPay Payment Module for osCommerce
 *
 * @copyright Copyright 2013-2014 Yellow Melon
 * @copyright Portions Copyright 2013 Paul Mathot
 * @copyright Portions Copyright 2003 osCommerce
 * @license see LICENSE.TXT
 */

/**
 * @class	TargetPay class for Zencart
 */

abstract class TargetPayClass 
{
	/**
	 * @var int rtlo partner I
	 */
	protected $intRtlo = 0;

	/**
	 * @desc construction class
     * @var int rtlo partner ID
	 * @param unknown_type $intRtlo
	 */
    public function __construct( $intRtlo ) 
    {
        $this->setRtlo ( $intRtlo );
    }
    
	/**
	 * @desc Get response for a targetpay request
	 * @param array $aParams
	 * @param string $sRequest
	 */
    protected function getResponse( $aParams, $sRequest = 'https://www.targetpay.com/api/plugandpay?'  ) 
    {
		# convert params
        $strParamString = $this->makeParamString( $aParams );
        # get request
        $strResponse = @file_get_contents( $sRequest . $strParamString);
        if ( $strResponse === false )
            throw new Exception('Could not fetch response');
        
        return $strResponse;
    }
    
    /**
     * @desc Make string from params
     * @param array $aParams
     * @return string
     */
   	protected function makeParamString( $aParams ) 
   	{
        $strString = '';
        foreach ( $aParams as $strKey => $strValue ) 
        	$strString .= '&' . urlencode($strKey) . '=' . urlencode($strValue);
        # remove first &  
        return substr( $strString ,1 )  ;          
    }
    
    /**
     * @desc Get the base request with IP, RTLO, domain,
     * @return array
     */
	protected function getBaseRequest() 
	{
		# return array with base parameters
		$aParams = array();
		$aParams['action'] = 'start';
		$aParams['ip'] = $_SERVER['REMOTE_ADDR'];
		$aParams['domain'] = $this->strDomain ;
		$aParams['rtlo'] = $this->intRtlo ;
        return $aParams;
    }

    /**
     * 
     * @param string $strDomain
     */
	public function setDomain ( $strDomain ) 
	{
		$this->strDomain = $strDomain;   
    }

    /**
     * @desc set rtlo partner id
     * @param int $intRtlo
     */
    public function setRtlo ( $intRtlo ) 
    {
        $this->intRtlo = $intRtlo;   
    }
    
    /**
     * @desc Return rtl
     * @return int     
     */
    public function getRtlo () 
    {
        return $this->intRtlo;   
    }     
}

?>
