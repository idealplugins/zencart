<?php
/**
 * TargetPay Payment Module for osCommerce
 *
 * @copyright Copyright 2013-2014 Yellow Melon
 * @copyright Portions Copyright 2013 Paul Mathot
 * @copyright Portions Copyright 2003 osCommerce
 * @license see LICENSE.TXT
 */

require_once ("targetpay/TargetPayIdeal.class.php");

$ywincludefile = realpath(dirname(__FILE__).'/../../extra_datafiles/targetpay.php');
require_once ($ywincludefile);

$availableLanguages = array("dutch", "english");
$langDir = (isset($_SESSION["language"]) && in_array($_SESSION["language"], $availableLanguages)) ? $_SESSION["language"] : "dutch";

$ywincludefile = realpath(dirname(__FILE__).'/../../languages/'.$langDir.'/modules/payment/targetpay.php');
require_once ($ywincludefile);

$ywincludefile = realpath(dirname(__FILE__).'/targetpay/targetpay.class.php');
require_once ($ywincludefile);

class targetpay {

    var $code, $title, $description, $enabled;

    var $rtlo;

    var $passwordKey;

    var $merchantReturnURL;
    var $expirationPeriod;
    var $transactionDescription;
    var $transactionDescriptionText;

    var $returnURL;
    var $reportURL;
    var $cancelURL;

    var $transactionID;
    var $purchaseID;
    var $directoryUpdateFrequency;

    var $error;
    var $bankUrl;

    var $targetpaymodule;

  	/**
  	 * @method targetpay inits the module
  	 */

  	function targetpay() {

    	global $order;

    	$this->code = 'targetpay';
   		$this->title = MODULE_PAYMENT_TARGETPAY_TEXT_TITLE;
    	$this->description = MODULE_PAYMENT_TARGETPAY_TEXT_DESCRIPTION;
    	$this->sort_order = MODULE_PAYMENT_TARGETPAY_SORT_ORDER;
    	$this->enabled = ((MODULE_PAYMENT_TARGETPAY_STATUS == 'True') ? true : false);

    	$this->rtlo = MODULE_PAYMENT_TARGETPAY_TARGETPAY_RTLO;

        $this->reportURL = ((ENABLE_SSL == 'true') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG . 'targetpay_callback.php?'.
            "zsn=".urlencode(zen_session_name())."&".
            "zenid=".zen_session_id();

        // $this->reportURL = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL') . "&action=process&".zen_session_name()."=".zen_session_id();
        // $this->returnURL = zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL');

        $this->cancelURL = zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');
        $this->returnURL = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL')."&action=process";

        if (MODULE_PAYMENT_TARGETPAY_TESTACCOUNT == "True") {
            $this->cancelURL = $this->returnURL;
        }

        $this->transactionDescription = MODULE_PAYMENT_TARGETPAY_TRANSACTION_DESCRIPTION;
    	
    	$this->targetpaymodule = new TargetPayIdealOld ($this->rtlo);
	  	if(MODULE_PAYMENT_TARGETPAY_REPAIR_ORDER === true) {
			if($_GET['targetpay_transaction_id']) {
				    $_SESSION['targetpay_repair_transaction_id'] = zen_db_input($_GET['targetpay_transaction_id']);
    			}
	      	$this->transactionID = $_SESSION['targetpay_repair_transaction_id'];
	    }
  	}

  	/**
  	 * @desc update module status
  	 */

    function update_status()
    {
        global $order, $db;

        if (($this->enabled == true) && ((int)MODULE_PAYMENT_TARGETPAY_ZONE > 0) ) {
            $check_flag = false;
            $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_TARGETPAY_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");

            while (!$check->EOF) {
                if ($check->fields['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                }
                elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
                $check->MoveNext();
            }
            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

	function javascript_validation()
  	{
    	return false;
  	}

  	/**
  	 * @desc get bank directory
  	 */
  	function getDirectory()
  	{
    	global $db;
	    $issuerList = array();

		$objTargetpay = new TargetPayCore ("AUTO",$this->rtlo);
		
		$bankList = $objTargetpay->getBankList();
		foreach($bankList AS $issuerID => $issuerName ) {
			$i = new stdClass();
			$i->issuerID = $issuerID;
			$i->issuerName = $issuerName;
			$i->issuerList = 'short';
			array_push($issuerList, $i);
		}
		
    	return $issuerList;
	}

	/**
	 * @desc make bank selection field
	 */
  	function selection()
    {
        global $order;

        $directory = $this->getDirectory();

        if(!is_null($directory))
        {
            $issuers = array();
            $issuerType = "Short";

            $issuers[] = array('id' => "-1", 'text' => MODULE_PAYMENT_TARGETPAY_TEXT_ISSUER_SELECTION);

            foreach ($directory as $issuer) {
                if($issuer->issuerList != $issuerType) {
                    $issuerType = $issuer->issuerList;
                }

                $issuers[] = array('id' => $issuer->issuerID, 'text' => $issuer->issuerName);
            }

            $selection = array( 'id' => $this->code,
                'module' => "", // $this->title . " ".MODULE_PAYMENT_TARGETPAY_TEXT_INFO
                'fields' => array(  array(  'title' => zen_image('images/icons/targetpay.png','','','','align=absmiddle'), // .MODULE_PAYMENT_TARGETPAY_TEXT_ISSUER_SELECTION
                'field' => zen_draw_pull_down_menu('bankID', $issuers, '', 'onChange="check_targetpay()"'))));
        }
        else {
            $selection = array( 'id' => $this->code,
                'module' => $this->title . MODULE_PAYMENT_TARGETPAY_TEXT_INFO,
                'fields' => array(  array(  'title' => MODULE_PAYMENT_TARGETPAY_TEXT_ISSUER_SELECTION,
                'field' => "Could not get banks. ".$this->targetpaymodule->getErrorMessage())));
        }
        return $selection;
    }

  	/**
  	 * @desc pre_confirmation_check
  	 */

  	function pre_confirmation_check()
  	{
    	global $messageStack;

    	if(!isset($_POST['bankID']) || ($_POST['bankID'] < 0)) {
            $messageStack->add_session('checkout_payment',MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_NO_ISSUER_SELECTED);
      		zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    	}
  	}

  	/**
  	 * @desc prepare the transaction and send user back on error or forward to bank
  	 */
  	function prepareTransaction()
    {
        global $order, $currencies, $customer_id, $db, $messageStack, $order_totals;

        if(!isset($_POST['bankID']) || ($_POST['bankID'] < 0)) {
            $messageStack->add_session('checkout_payment',MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_NO_ISSUER_SELECTED);
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        $ideal_issuerID = zen_db_input($_POST['bankID']); //bank
        $ideal_purchaseID = time();
        $ideal_currency = "EUR"; //future use
        $ideal_language = "nl"; //future use 
        $ideal_amount = round($order->info['total'] * 100, 0);
        $ideal_entranceCode = zen_session_id();

        if((strtolower($this->transactionDescription) == 'automatic')&&(count($order->products) == 1)){
            $product = $order->products[0];
            $ideal_description = $product['name'];
        } else {
            $ideal_description = $this->transactionDescriptionText;
        }

        $ideal_description = trim(strip_tags($ideal_description));
        $ideal_description = preg_replace("/[^A-Za-z0-9 ]/", '*', $ideal_description);
        $ideal_description = substr($ideal_description,0,31); /* Max. 32 characters */

        if(empty($ideal_description)) $ideal_description = 'nvt';

        if($this->targetpaymodule->setIdealAmount($ideal_amount) === false){
            $messageStack->add_session('checkout_payment',MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_ERROR_OCCURRED_PROCESSING."<br/>".$this->targetpaymodule->getError());
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        } else {
            $appId = '7e38711946be5dbe3267e9ef31c36702';
            $objTargetpay = new TargetPayCore ("AUTO",$this->rtlo,$appId);
            $objTargetpay->setBankId($ideal_issuerID);
            $objTargetpay->setAmount($ideal_amount);
            $objTargetpay->setDescription($ideal_description);

            $objTargetpay->setReportUrl($this->reportURL.'&method=%payMethod%');
            $objTargetpay->setReturnUrl($this->returnURL.'&method=%payMethod%');
            $objTargetpay->setCancelUrl($this->cancelURL.'&method=%payMethod%');
            $result = @$objTargetpay->startPayment();

            if($result === false) {
                $messageStack->add_session('checkout_payment',MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_ERROR_OCCURRED_PROCESSING . "<br/>".$objTargetpay->getErrorMessage());
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            }

            $this->transactionID = $objTargetpay->getTransactionId();

            if(!is_numeric($this->transactionID)) {
                $messageStack->add_session('checkout_payment',MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_ERROR_OCCURRED_PROCESSING. "<br/>".$objTargetpay->getErrorMessage());
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            }

            $this->bankUrl = $objTargetpay->getBankUrl();

            if(MODULE_PAYMENT_TARGETPAY_EMAIL_ORDER_INIT == 'True') {    
                $email_text = 'Er is zojuist een Targetpay iDEAL bestelling opgestart' . "\n\n";
                $email_text .= 'Details:' . "\n";      
                $email_text .= 'customer_id: ' . $_SESSION['customer_id'] . "\n";
                $email_text .= 'customer_first_name: ' . $_SESSION['customer_first_name'] . "\n";
                $email_text .= 'TargetPay transaction_id: ' . $this->transactionID . "\n";    
                $email_text .= 'bedrag: ' . $ideal_amount . ' (' . $ideal_currency . 'x100)' . "\n";
                $max_orders_id = $db->Execute("select max(orders_id) orders_id from " . TABLE_ORDERS );
                $new_order_id = $max_orders_id->fields['orders_id'] +1;   
                $email_text .= 'order_id: ' . $new_order_id . ' (verwacht indien de bestelling wordt voltooid, kan ook hoger zijn)' . "\n"; 
                $email_text .= "\n\n"; 
                $email_text .= 'Targetpay transactions lookup: ' . HTTP_SERVER_TARGETPAY_ADMIN . FILENAME_TARGETPAY_TRANSACTIONS . '?action=lookup&transactionID=' . $this->transactionID . "\n";     
                zen_mail('', STORE_OWNER_EMAIL_ADDRESS, '[iDeal bestelling opgestart] #' . $new_order_id . ' (?)', $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }

            $db->Execute("INSERT INTO " . TABLE_TARGETPAY_TRANSACTIONS . " ( `transaction_id` , `rtlo`, `purchase_id` , `issuer_id` ,`transaction_status` , `datetimestamp` , `customer_id` , `amount` , `currency`, `session_id`, `ideal_session_data`) VALUES ('".$this->transactionID."', '".$this->rtlo."', '".$ideal_purchaseID."', '" . $ideal_issuerID . "', 'open', NOW( ), '".$_SESSION['customer_id']."', '".$ideal_amount."', '".$ideal_currency."', '" . zen_db_input(zen_session_id()) . "', '" . base64_encode(serialize($_SESSION)) . "');");
            zen_redirect(html_entity_decode($this->bankUrl));
        }
    }

  	/**
  	 * @return false
  	 */
  	function confirmation()
  	{
   		return false;
  	}

  	/**
  	 * @desc make hidden value for payment system
  	 */
  	function process_button()
  	{
    	$process_button = zen_draw_hidden_field('bankID', $_POST['bankID']) . MODULE_PAYMENT_TARGETPAY_EXPRESS_TEXT;     

    	if(defined('BUTTON_CHECKOUT_TARGETPAY_ALT')){
      		$process_button .= zen_image_submit('targetpay.gif', BUTTON_CHECKOUT_TARGETPAY_ALT);
    	}
    	return  $process_button;
  	}

  	/**
  	 * @desc before process check status or prepare transaction
  	 */
  	function before_process()
  	{
	  	if(MODULE_PAYMENT_TARGETPAY_REPAIR_ORDER === true)
	  	{ 
			global $order;
			// when repairing iDeal the transaction status is succes, set order status accordingly
			$order->info['order_status'] = MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID;
			return false;
	    }
    	if(isset($_GET['action']) && $_GET['action'] == "process")
      		$this->checkStatus();
    	else
      		$this->prepareTransaction();
  	}

  	/**
  	 * @desc check payment status
  	 */
  	function checkStatus()
  	{
    	global $order, $db, $messageStack;

  		if(MODULE_PAYMENT_TARGETPAY_REPAIR_ORDER === true){ return false; }
    	$this->transactionID = zen_db_input($_GET['trxid']);
    	$method = zen_db_input($_GET['method']);

    	if($this->transactionID == "")
    	{
            $messageStack->add_session('checkout_payment',MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_ERROR_OCCURRED_PROCESSING);
      		zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    	}

		$objTargetpay = new TargetPayCore ($method,$this->rtlo);
		$status = $objTargetpay->checkPayment($this->transactionID);
		
		if($objTargetpay->getPaidStatus()) {
			$realstatus = "success";
		} else {
			$realstatus = "open";
		}

        if (MODULE_PAYMENT_TARGETPAY_TESTACCOUNT == "True") {
          $realstatus = "success"; // Test mode = always OK!
        }

		$customerInfo = $objTargetpay->getConsumerInfo();
		$consumerAccount = (((isset($customerInfo->consumerInfo["bankaccount"]) && !empty($customerInfo->consumerInfo["bankaccount"])) ? $customerInfo->consumerInfo["bankaccount"] : ""));
		$consumerName = (((isset($customerInfo->consumerInfo["name"]) && !empty($customerInfo->consumerInfo["name"])) ? $customerInfo->consumerInfo["name"] : ""));
		$consumerCity = (((isset($customerInfo->consumerInfo["city"]) && !empty($customerInfo->consumerInfo["city"])) ? $customerInfo->consumerInfo["city"] : ""));
		
    	$db->Execute("UPDATE " . TABLE_TARGETPAY_TRANSACTIONS . " SET `transaction_status` = '".$realstatus."',`datetimestamp` = NOW( ) ,`consumer_name` = '".$consumerName."',`consumer_account_number` = '".$consumerAccount."',`consumer_city` = '".$consumerCity."' WHERE `transaction_id` = '".$this->transactionID."' LIMIT 1");


    	switch ($realstatus)
    	{
     		case "success": 
            	$order->info['order_status'] = MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID;
            	break;
     		case "open":
	     		$messageStack->add_session('checkout_payment',MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_OPEN);
	            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
     			break;
     		default:
            	$messageStack->add_session('checkout_payment',MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_OPEN);
            	zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            break;
    	}
  	}

  	/**
  	 * @desc after order create set value in database
  	 * @param $zf_order_id
  	 */
  	function after_order_create($zf_order_id)
  	{
    	global $db;
    	$db->Execute("UPDATE " . TABLE_TARGETPAY_TRANSACTIONS . " SET `order_id` = '".$zf_order_id."', `ideal_session_data` = '' WHERE `transaction_id` = '".$this->transactionID."' LIMIT 1 ;");
	  	if(isset($_SESSION['targetpay_repair_transaction_id']))
	  	{
			unset($_SESSION['targetpay_repair_transaction_id']);
	    }
  	}

  	/**
  	 * @desc after process function
  	 * @return false
  	 */
  	function after_process() {
        return false;
    }

    /**
     * @desc checks installation of module
     */
  	function check()
  	{
        global $db;
            if (!isset($this->_check))
    	{
      		$check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_TARGETPAY_STATUS'");
      		$this->_check = $check_query->RecordCount();
    	}
    	return $this->_check;
  	}

  	/**
  	 * @desc install values in database
  	 */
  	function install()
  	{
        global $db;

    	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Targetpay payment module', 'MODULE_PAYMENT_TARGETPAY_STATUS', 'True', 'Do you want to accept Targetpay payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sortorder', 'MODULE_PAYMENT_TARGETPAY_SORT_ORDER', '0', 'Sort order of payment methods in list. Lowest is displayed first.', '6', '2', now())");
    	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment zone', 'MODULE_PAYMENT_TARGETPAY_ZONE', '0', 'If a zone is selected, enable this payment method for that zone only.', '6', '3', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");

	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Order status - confirmed', 'MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID', '" . DEFAULT_ORDERS_STATUS_ID .  "', 'The status of orders that where successfully confirmed. (Recommended: <strong>processing</strong>)', '6', '4', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Order status - open', 'MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID_OPEN', '" . DEFAULT_ORDERS_STATUS_ID .  "', 'The status of orders of which payment could not be confirmed. (Recommended: <strong>pending</strong>)', '6', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction description', 'MODULE_PAYMENT_TARGETPAY_TRANSACTION_DESCRIPTION', 'Automatic', 'Select automatic for product name as description, or manual to use the text you supply below.', '6', '8', 'zen_cfg_select_option(array(\'Automatic\',\'Manual\'), ', now())");
	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Transaction description text', 'MODULE_PAYMENT_TARGETPAY_MERCHANT_TRANSACTION_DESCRIPTION_TEXT', '" . TITLE . "', 'Description of transactions from this webshop. <strong>Should not be empty!</strong>.', '6', '8', now())");

	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Targetpay ID', 'MODULE_PAYMENT_TARGETPAY_TARGETPAY_RTLO', '93929', 'The Targetpay RTLO', '6', '4', now())");// Default TargetPay

	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Testaccount?', 'MODULE_PAYMENT_TARGETPAY_TESTACCOUNT', 'False', 'Enable testaccount (only for validation)?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
	    
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('IP address', 'MODULE_PAYMENT_TARGETPAY_REPAIR_IP', '" . $_SERVER['REMOTE_ADDR'] . "', 'The IP address of the user (administrator) that is allowed to complete open ideal orders (if empty everyone will be allowed, which is not recommended!).', '6', '8', now())");
    	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable pre order emails', 'MODULE_PAYMENT_TARGETPAY_EMAIL_ORDER_INIT', 'False', 'Do you want emails to be sent to the store owner whenever an Targetpay order is being initiated? The default is <strong>False</strong>.', '6', '17', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");    

    	$db->Execute("CREATE TABLE " . TABLE_TARGETPAY_DIRECTORY . " (`issuer_id` VARCHAR( 4 ) NOT NULL ,`issuer_name` VARCHAR( 30 ) NOT NULL ,`issuer_issuerlist` VARCHAR( 5 ) NOT NULL ,`timestamp` DATETIME NOT NULL ,PRIMARY KEY ( `issuer_id` ) );");

    	if(TARGETPAY_OLD_MYSQL_VERSION_COMP == 'true'){
      		$db->Execute("CREATE TABLE IF NOT EXISTS " . TABLE_TARGETPAY_TRANSACTIONS . " (`transaction_id` VARCHAR( 30 ) NOT NULL ,`rtlo` VARCHAR( 7 ) NOT NULL ,`purchase_id` VARCHAR( 30 ) NOT NULL , `issuer_id` VARCHAR( 25 ) NOT NULL , `session_id` VARCHAR( 128 ) NOT NULL ,`ideal_session_data`  MEDIUMBLOB NOT NULL ,`order_id` INT( 11 ),`transaction_status` VARCHAR( 10 ) ,`datetimestamp` DATETIME, `consumer_name` VARCHAR( 50 ) ,`consumer_account_number` VARCHAR( 20 ) ,`consumer_city` VARCHAR( 50 ), `customer_id` INT( 11 ), `amount` DECIMAL( 15, 4 ), `currency` CHAR( 3 ), `batch_id` VARCHAR( 30 ), PRIMARY KEY ( `transaction_id` ));");
    	}else{
      		$db->Execute("CREATE TABLE IF NOT EXISTS " . TABLE_TARGETPAY_TRANSACTIONS . " (`transaction_id` VARCHAR( 30 ) NOT NULL ,`rtlo` VARCHAR( 7 ) NOT NULL ,`purchase_id` VARCHAR( 30 ) NOT NULL , `issuer_id` VARCHAR( 25 ) NOT NULL , `session_id` VARCHAR( 128 ) NOT NULL ,`ideal_session_data`  MEDIUMBLOB NOT NULL ,`order_id` INT( 11 ),`transaction_status` VARCHAR( 10 ) ,`datetimestamp` DATETIME, `last_modified` TIMESTAMP NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, `consumer_name` VARCHAR( 50 ) ,`consumer_account_number` VARCHAR( 20 ) ,`consumer_city` VARCHAR( 50 ), `customer_id` INT( 11 ), `amount` DECIMAL( 15, 4 ), `currency` CHAR( 3 ), `batch_id` VARCHAR( 30 ), PRIMARY KEY ( `transaction_id` ));");
    	}
  	}

  	function remove()
  	{
    	global $db;

    	$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");

    	$db->Execute("DROP TABLE IF EXISTS " . TABLE_TARGETPAY_TRANSACTIONS);
    	$db->Execute("DROP TABLE IF EXISTS " . TABLE_TARGETPAY_DIRECTORY);
  	}

  	function keys()
  	{
    	return array('MODULE_PAYMENT_TARGETPAY_STATUS','MODULE_PAYMENT_TARGETPAY_SORT_ORDER','MODULE_PAYMENT_TARGETPAY_ZONE','MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID','MODULE_PAYMENT_TARGETPAY_TESTACCOUNT','MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID_OPEN','MODULE_PAYMENT_TARGETPAY_TARGETPAY_RTLO' , 'MODULE_PAYMENT_TARGETPAY_TRANSACTION_DESCRIPTION', 'MODULE_PAYMENT_TARGETPAY_MERCHANT_TRANSACTION_DESCRIPTION_TEXT',  'MODULE_PAYMENT_TARGETPAY_EMAIL_ORDER_INIT','MODULE_PAYMENT_TARGETPAY_REPAIR_IP');
  	}
}

?>
