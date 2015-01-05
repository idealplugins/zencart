<?php
/**

	TargetPay module class for Zencart
	(C) Copyright Yellow Melon B.V. 2013

*/
	
require('includes/application_top.php');

$ywincludefile = realpath(dirname(__FILE__).'/includes/languages/dutch/targetpay_transactions.php');
require_once ($ywincludefile);

$ywincludefile = realpath(dirname(__FILE__).'/../includes/modules/payment/targetpay/targetpay.class.php');
require_once ($ywincludefile);

	/**
	 * 
	 */
	function targetpay_get_directorylist()
	{
		$issuerList = array();

		$objTargetpay = new TargetPayCore ("AUTO");

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
	 * 
	 * @param $date
	 */
	function get_unix_timestamp($date)
	{
		return strtotime($date);
	}

	function timeDiff($timestamp)
	{
  		$now = time();
		if($now < $timestamp)
		{
			$sign = '-';
			$elapsed =  $timestamp - $now;
		}
		else
		{
			$sign = '';
			$elapsed = ($now - $timestamp);
		}

		if($elapsed > 604800)
		{
    		$return = ' meer dan een week';
  		}
  		elseif($elapsed > 172800)
  		{
    		$return = gmdate('z', $elapsed) . ' dagen';
  		}
  		elseif($elapsed > 86400)
  		{
    		$return = gmdate('z', $elapsed) . ' dag';
  		}
  		else
  		{
    		$return = gmdate('G:i:s', $elapsed);
  		}
  
  		if(($now > $timestamp) && ($elapsed > (int)MODULE_PAYMENT_TARGETPAY_EXPIRATION_PERIOD))
  		{
   			$return = '<strong class="targetpayExpiredTransaction">' . $return . '</strong>';
  		}
  		return $sign . $return;
	}

	/**
	 * 
	 * @param integer $transactionID
	 */
	function targetpay_lookup_transaction($transactionID='')
	{
  		global $db;
		$selected_transaction_query = "SELECT it.*, o.orders_status, s.orders_status_name FROM (" . TABLE_TARGETPAY_TRANSACTIONS . " it LEFT OUTER JOIN " . TABLE_ORDERS . " o ON (it.order_id = o.orders_id)) LEFT OUTER JOIN " . TABLE_ORDERS_STATUS . " s ON (o.orders_status = s.orders_status_id) WHERE (s.language_id = '" . (int)$_SESSION['languages_id'] . "' OR s.language_id IS NULL) AND (it.transaction_id = '" . $transactionID . "' OR it.order_id = '" . $transactionID . "') ORDER BY it.datetimestamp DESC";    
		$selected_transaction = $db->Execute($selected_transaction_query);
  		return $selected_transaction;
	}

	function targetpay_check_transaction_status($transactionID='')
	{
  		global $messageStack, $db;
 
  		if (!zen_not_null($transactionID))
  		{
    		return;
  		}
 
  		require_once(DIR_FS_CATALOG_MODULES . "payment/targetpay/targetpay.class.php");
    	
    	$selected_transaction = targetpay_lookup_transaction($transactionID);

    	$objTargetpay = new TargetPayCore ("AUTO",$selected_transaction->fields["rtlo"]);
    	$objTargetpay->setBankId($selected_transaction->fields["issuer_id"]);
    	
    	
    	
		$objTargetpay->checkPayment($transactionID);
		
		
		$realstatus = "Open";
		if($objTargetpay->getPaidStatus()) {
			$realstatus = "Success";
		} else {
			list($errorcode,$void) = explode(" ", $objTargetpay->getErrorMessage());
			switch ($errorcode) {
				case "TP0010":
					$realstatus = "Open";
					break;
				case "TP0011":
					$realstatus = "Cancelled";
					break;
				case "TP0012":
					$realstatus = "Expired";
					break;
				case "TP0013":
					$realstatus = "Failure";
					break;
				case "TP0014":
					$realstatus = "Success";
					break;
				default:
					$realstatus = "Open";
			}
		}
		$customerInfo = $objTargetpay->getConsumerInfo();
		$consumerAccount = (((isset($customerInfo->consumerInfo["bankaccount"]) && !empty($customerInfo->consumerInfo["bankaccount"])) ? $customerInfo->consumerInfo["bankaccount"] : ""));
		$consumerName = (((isset($customerInfo->consumerInfo["name"]) && !empty($customerInfo->consumerInfo["name"])) ? $customerInfo->consumerInfo["name"] : ""));
		$consumerCity = (((isset($customerInfo->consumerInfo["city"]) && !empty($customerInfo->consumerInfo["city"])) ? $customerInfo->consumerInfo["city"] : ""));
		
    	
  		if($realstatus != "")
  		{
    		$status_update = $db->Execute("UPDATE " . TABLE_TARGETPAY_TRANSACTIONS . " SET transaction_status = '" . $realstatus . "' , consumer_name = '" . $consumerName . "', consumer_account_number = '" . $consumerAccountNumber . "', consumer_city = '" . $consumerCity . "' WHERE transaction_id = '" . $transactionID . "' LIMIT 1");
    		if($status_update->resource)
    		{
      			$messageStack->add_session(TARGETPAY_MESSAGE_SUCCESS_STATUS." statuscode: ok",'success');
    		}
    		else
    		{
      			$messageStack->add_session(TARGETPAY_MESSAGE_WARNING_STATUS." statuscode:".$objTargetpay->getErrorMessage(),'warning');
    		}
  		}
  		else
  		{
    		$messageStack->add_session(MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_OPEN, 'warning');
  		}
      //~ 
  		/* reload the page to display the messageStack, and pass params to show transaction details again */
  		zen_redirect(zen_href_link(FILENAME_TARGETPAY_TRANSACTIONS,'action=lookup&transactionID=' . $transactionID));
	}
	/**
	 * Set the action to be carried out on iDeal transactions that need further
	 * processing.
	 *
	 * "open" transactions need to be queried again.
	 * "Success" transactions need to be updated to the proper order status.
	 *
	 */
	function getSuggestedAction($transactionID, $transactionStatus, $orderID, $orderStatus, $session_id = '', $age = 0)
	{
  		if(strtolower($transactionStatus) == "open")
  		{
    		return array( "text" => TARGETPAY_TEXT_CHECK_STATUS, 
                  "link" => zen_href_link(FILENAME_TARGETPAY_TRANSACTIONS,"action=checkstatus&transactionID=".$transactionID),
                  'parameters' => ''                  
                  );
  		}
  		if(($transactionStatus == "success") && !($orderID > 0) && ($age > 0) &&($age < (int)MODULE_PAYMENT_TARGETPAY_EXPIRATION_PERIOD))
  		{
    		return array( "text" => TARGETPAY_WAIT_OR_CREATE_ORDER,
                  "link" => HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'index.php?main_page=checkout_process&targetpay_repair_order=true&targetpay_transaction_id=' . $transactionID,
                  'parameters' => 'target="_blank" onClick="return confirm(\'' . IDEAL_TEXT_ARE_YOU_SURE_WAIT . '\')"'
                  );
  		}  
  		if(($transactionStatus == "success") && !($orderID > 0))
  		{
    		return array( "text" => TARGETPAY_TEXT_CREATE_ORDER,
                  "link" => HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'index.php?main_page=checkout_process&targetpay_repair_order=true&targetpay_transaction_id=' . $transactionID,
                  'parameters' => 'target="_blank" onClick="return confirm(\'' . TARGETPAY_TEXT_ARE_YOU_SURE . '\')"'
                  );
  		}
  		if($transactionStatus == "success" && ($orderStatus == MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID_OPEN || $orderStatus == 0))
  		{
    		return array( "text" => TARGETPAY_TEXT_CHANGE_STATUS,
                  "link" => zen_href_link(FILENAME_ORDERS, "action=edit&oID=" . $orderID),
                  'parameters' => 'target="_blank"'                  
                  );
  		}
  		return array( "text" => TARGETPAY_TEXT_NO_ACTION,
        	"link" => "#",
            'parameters' => ''                
        ); 
	}

	/**
	 * @desc cleanup non open or success transactions
	 */
	function targetpay_cleanup()
	{
 		global $db;
		$db->Execute("DELETE FROM " . TABLE_TARGETPAY_TRANSACTIONS . " WHERE (transaction_status='Expired' OR transaction_status='Cancelled' OR transaction_status='Failure')");
		$db->Execute("UPDATE " . TABLE_TARGETPAY_TRANSACTIONS . " SET `ideal_session_data` = '' WHERE (`order_id` > 0 AND `ideal_session_data` != '')");
	}

	/**
	 * 
	 * @param integer $transaction_id
	 */
	function targetpay_delete_transaction($transaction_id = '')
	{
  		global $db, $messageStack;
 		if($transaction_id != '')
 		{
			$db->Execute("DELETE FROM " . TABLE_TARGETPAY_TRANSACTIONS . " WHERE `transaction_id`='" . $transaction_id . "'");
    		$messageStack->add('Removed transaction: ' . $transaction_id,'success');
		}
	}

	if(is_file(DIR_WS_MODULES . 'targetpay_csv_import_module.php'))
	{
  		include(DIR_WS_MODULES . 'targetpay_csv_import_module.php');
	}

	if($_GET['action'] == 'remove')
	{
		targetpay_delete_transaction($_GET['transactionID']);
	}
 
	$transFilter = TARGETPAY_TEXT_FILTER_INCOMPLETE;

	if (isset($_GET['transFilter']))
	{
 		$transFilter = $_GET['transFilter'];
	}

	if (isset($_GET['transactionID']))
	{
 		$transactionID = zen_db_input(zen_db_prepare_input($_GET['transactionID']));
	}

	$transaction_query_raw = "SELECT it.*, o.orders_status, s.orders_status_name FROM (" . TABLE_TARGETPAY_TRANSACTIONS . " it LEFT OUTER JOIN " . TABLE_ORDERS . " o ON (it.order_id = o.orders_id)) LEFT OUTER JOIN " . TABLE_ORDERS_STATUS . " s ON (o.orders_status = s.orders_status_id) WHERE (s.language_id = '" . (int)$_SESSION['languages_id'] . "' OR s.language_id IS NULL)";

	switch ($transFilter)
	{
  		case TARGETPAY_TEXT_FILTER_COMPLETE:
    		$transactions_query = $transaction_query_raw . " AND NOT ((it.transaction_status = 'success' AND (it.order_id IS NULL OR o.orders_status IS NULL OR o.orders_status='" . MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID_OPEN . "' OR o.orders_status='0')) OR it.transaction_status = 'open')";
    		break;
		case TARGETPAY_TEXT_FILTER_ALL:
			$transactions_query = $transaction_query_raw;
			break;
		case TARGETPAY_TEXT_FILTER_INCOMPLETE:
		default:
			$transactions_query = $transaction_query_raw . " AND ((it.transaction_status = 'success' AND (it.order_id IS NULL OR o.orders_status IS NULL OR o.orders_status='" . MODULE_PAYMENT_TARGETPAY_ORDER_STATUS_ID_OPEN . "' OR o.orders_status='0')) OR it.transaction_status = 'open')";
    	break;
	}
	$transactions_query .= " ORDER BY it.datetimestamp DESC";

	$transactions_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $transactions_query, $transactions_query_numrows);
	$transactions = $db->Execute($transactions_query);

	switch ($_GET['action'])
	{
  		case 'checkstatus':
    		targetpay_check_transaction_status($transactionID);
    		break;
  		case 'lookup':
    		if(isset($transactionID))
    		{
				$selected_transaction = targetpay_lookup_transaction($transactionID);
    		}
    		elseif (isset($_GET['orderID']))
    		{
      			$selected_transaction = targetpay_lookup_transaction(zen_db_input(zen_db_prepare_input($_GET['orderID'])));
    		}
   			break;
  		case 'cleanup':
    		targetpay_cleanup();
    		break;
	}
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo TITLE; ?></title>
<meta name="robot" content="noindex, nofollow" />
<script language="JavaScript" src="includes/menu.js" type="text/JavaScript"></script>
<link href="includes/stylesheet.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS" />
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
</head>
<body onLoad="init()">
<!-- header //-->
<?php
require(DIR_WS_INCLUDES . 'header.php');
?>
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
	      			<tr>
	        			<td width="100%">
	        				<table border="0" width="100%" cellspacing="0" cellpadding="0">
	          					<tr>
	            					<td class="pageHeading">TargetPay iDEAL Transactions</td>
	            					<td>
				            			<table border="0" width="100%" cellspacing="0" cellpadding="0">
				              				<tr>
				              					<?php echo zen_draw_form('transactions', FILENAME_TARGETPAY_TRANSACTIONS, '', 'get'); ?>
				                					<td class="smallText" align="center"><?php echo zen_draw_radio_field('transFilter', TARGETPAY_TEXT_FILTER_ALL, false,$transFilter, 'onchange="this.form.submit();"') . TARGETPAY_TEXT_FILTER_ALL . zen_draw_radio_field('transFilter', TARGETPAY_TEXT_FILTER_INCOMPLETE ,false,$transFilter, 'onchange="this.form.submit();"') . TARGETPAY_TEXT_FILTER_INCOMPLETE . zen_draw_radio_field('transFilter', TARGETPAY_TEXT_FILTER_COMPLETE, false, $transFilter, 'onchange="this.form.submit();"') . TARGETPAY_TEXT_FILTER_COMPLETE; ?> <input type="submit" value="Filter" /></td>
				              					</form>
				              				</tr>
				              				<tr>
				              					<?php echo zen_draw_form('transactions', FILENAME_TARGETPAY_TRANSACTIONS, '', 'get'); ?>
				               						<td class="smallText" align="center"><?php echo zen_draw_hidden_field('action', 'cleanup'); ?><input onClick="return confirm('Weet u zeker dat u de transatie tabel nu wilt opschonen?')" type="submit" value="<?php echo TARGETPAY_TEXT_FILTER_CLEANUP; ?>" /><?php echo TARGETPAY_TEXT_CLEANUP; ?></td>
				              					</form>
				              				</tr>
				            			</table>
	            					</td>
	           	 					<td align="right">
	           	 						<table border="0" width="100%" cellspacing="0" cellpadding="0">
	             						 	<tr>
	              								<?php echo zen_draw_form('transactions', FILENAME_TARGETPAY_TRANSACTIONS, '', 'get'); ?>
	                								<td class="smallText" align="right"><?php echo TARGETPAY_TEXT_SEARCH_TRANSACTION_ID . ' ' . zen_draw_input_field('transactionID', '', 'size="12"') . zen_draw_hidden_field('action', 'lookup') . zen_draw_hidden_field('transFilter', $transFilter); ?></td>
	              								</form>
	              							</tr>
	              							<tr>
					              				<?php echo zen_draw_form('transactions', FILENAME_TARGETPAY_TRANSACTIONS, '', 'get'); ?>
					                				<td class="smallText" align="right"><?php echo TARGETPAY_TEXT_SEARCH_ORDER_ID . ' ' . zen_draw_input_field('orderID', '', 'size="12"') . zen_draw_hidden_field('action', 'lookup') . zen_draw_hidden_field('transFilter', $transFilter); ?></td>
					              				</form>
	              							</tr>
	            						</table>
	            					</td>
	          					</tr>
	        				</table>
	        			</td>
     				</tr>
					<tr>
				        <td>
				        	<table border="0" width="100%" cellspacing="0" cellpadding="0">
				          		<tr>
						            <td valign="top">
						            	<table border="0" width="100%" cellspacing="0" cellpadding="2">
						              		<tr class="dataTableHeadingRow">
								                <td class="dataTableHeadingContent"><?php echo TARGETPAY_TABLE_HEADING_TRANSACTION_ID; ?></td>
								                <td class="dataTableHeadingContent">RTLO</td>
								                <td class="dataTableHeadingContent"><?php echo TARGETPAY_TABLE_HEADING_PURCHASE_ID; ?></td>
								                <td class="dataTableHeadingContent"><?php echo TARGETPAY_TABLE_HEADING_ORDER_ID; ?></td>
								                <td class="dataTableHeadingContent"><?php echo TARGETPAY_TABLE_HEADING_TRANSACTION_STATUS; ?></td>
								                <td class="dataTableHeadingContent"><?php echo TARGETPAY_TABLE_HEADING_ORDER_STATUS; ?></td>
								                <td class="dataTableHeadingContent" align="right"><?php echo TARGETPAY_TABLE_HEADING_TRANSACTION_DATE_TIME; ?></td>
								                <td class="dataTableHeadingContent" align="right"><?php echo TARGETPAY_TABLE_HEADING_AGE; ?></td>
								                <td class="dataTableHeadingContent" align="right"><?php echo TARGETPAY_HEADING_CUSTOMER_ID; ?></td>
						               			<td class="dataTableHeadingContent" align="right"><?php echo TARGETPAY_TABLE_HEADING_SUGGESTED_ACTION; ?></td>
						                		<td class="dataTableHeadingContent" align="right"><?php echo 'Details'; ?></td>                
						              		</tr>
<?php
while (!$transactions->EOF)
{
?> 
				              				<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                <td class="dataTableContent"><?php echo $transactions->fields['transaction_id']; ?></td>
								                <td class="dataTableContent"><?php echo $transactions->fields['rtlo']; ?></td>
								                <td class="dataTableContent"><?php echo $transactions->fields['purchase_id']; ?></td>
								                <td class="dataTableContent"><a href="<?php echo zen_href_link(FILENAME_ORDERS)."?oID=".$transactions->fields['order_id']; ?>&action=edit"><?php echo $transactions->fields['order_id']; ?></a></td>
								                <td class="dataTableContent"><?php echo $transactions->fields['transaction_status']; ?></td>
								                <td class="dataTableContent"><?php echo ($transactions->fields['orders_status']=='0' ? '0' : $transactions->fields['orders_status_name']); ?></td>
								                <td class="dataTableContent" align="right"><?php echo zen_datetime_short($transactions->fields['datetimestamp']); ?></td>
												<td class="dataTableContent" align="right"><?php
$transaction_age = 0;

if(((!($transactions->fields['order_id'] > 0))&&(strtolower($transactions->fields['transaction_status']) == 'success'))||(strtolower($transactions->fields['transaction_status']) == 'open')){
  echo  timeDiff(get_unix_timestamp($transactions->fields['datetimestamp']));
  $transaction_age = time() - get_unix_timestamp($transactions->fields['datetimestamp']);
}else{
  echo 'n.v.t';
}
?></td>
												
								                <td class="dataTableContent" align="right">
								                	<a target="_blank" href="<?php echo zen_href_link(FILENAME_CUSTOMERS,'selected_box=customers&cID=' . $transactions->fields['customer_id'],'NONSSL')?>"><?php echo $transactions->fields['customer_id'];?></a>
								                </td>
								                <td class="dataTableContent" align="right">
<?php 
	$res = getSuggestedAction($transactions->fields['transaction_id'],$transactions->fields['transaction_status'],$transactions->fields['order_id'],$transactions->fields['orders_status'], $transactions->fields['session_id'], $transaction_age);
    if(($res['link'] != '#') && !empty($res['link'])){
?>
	<a <?php echo $res['parameters']; ?>href="<?php echo $res['link']?>"><?php echo $res['text']; ?></a>
<?php
	}
	else
	{
		echo '--';  
    }
?></td>
								                <td class="dataTableContent" align="right"><a href="<?php echo zen_href_link(FILENAME_TARGETPAY_TRANSACTIONS, zen_get_all_get_params(array('action','transactionID')) . 'action=lookup&transactionID=' .$transactions->fields['transaction_id'],'NONSSL')?>"><?php echo TARGETPAY_TEXT_DETAILS;?></a>&nbsp;</td>
				              				</tr>
<?php
  $transactions->MoveNext();
}
?>
							              	<tr>
							                	<td colspan="5">
							                		<table border="0" width="100%" cellspacing="0" cellpadding="2">
							                  			<tr>
							                    			<td class="smallText" valign="top"><?php echo $transactions_split->display_count($transactions_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></td>
							                    			<td class="smallText" align="right"><?php echo $transactions_split->display_links($transactions_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], zen_get_all_get_params(array('page', 'oID', 'action'))); ?></td>
							                  			</tr>
							                		</table>
							                	</td>
							              	</tr>
				            			</table>

<?php
if (is_object($selected_transaction) && !$selected_transaction->EOF)
{
  
$transaction_age = 0;
if(!($selected_transaction->fields['order_id'] > 0)){
  //echo  timeDiff(get_unix_timestamp($transactions->fields['datetimestamp']));
  $selected_transaction_age = time() - get_unix_timestamp($selected_transaction->fields['datetimestamp']);
} 
?>
				            			<hr /> 
				            			<table border="0" width="100%" cellspacing="0" cellpadding="0">
				              				<tr>
				                				<td valign="top">
				                					<table border="0" width="100%" cellspacing="0" cellpadding="2">
				                  						<tr class="dataTableHeadingRow">
				                    						<td class="dataTableHeadingContent" colspan="2"><?php echo TARGETPAY_TABLE_HEADING_TRANSACTION_DETAILS; ?></td>
				                  						</tr>
				                  
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo 'Verwijder deze transactie uit de tabel' ?></td>
								                    		<td class="dataTableContent">
								                      			<a onclick="return confirm('Weet u zeker dat u deze transactie wilt verwijderen uit de TargetPay iDEAL tabel?')" href="<?php echo zen_href_link(FILENAME_TARGETPAY_TRANSACTIONS, zen_get_all_get_params(array('action','transactionID')) . 'action=remove&transactionID=' .$selected_transaction->fields['transaction_id'],'NONSSL')?>">Verwijder</a>
								                    		</td>
								                  		</tr>                  
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_TABLE_HEADING_TRANSACTION_ID; ?></td>
								                    		<td class="dataTableContent"><?php echo $selected_transaction->fields['transaction_id']; ?></td>
								                  		</tr>
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent">RTLO</td>
								                    		<td class="dataTableContent"><?php echo $selected_transaction->fields['rtlo']; ?></td>
								                  		</tr>
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_TABLE_HEADING_PURCHASE_ID; ?></td>
								                    		<td class="dataTableContent"><?php echo $selected_transaction->fields['purchase_id']; ?></td>
								                  		</tr>
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_TABLE_HEADING_ORDER_ID; ?></td>
								                   			<td class="dataTableContent"><a href="<?php echo zen_href_link(FILENAME_ORDERS)."?page=1&oID=".$selected_transaction->fields['order_id']; ?>"><?php echo $selected_transaction->fields['order_id']; ?></a></td>
								                  		</tr>
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_TABLE_HEADING_TRANSACTION_STATUS; ?></td>
								                   			<td class="dataTableContent"><?php echo $selected_transaction->fields['transaction_status']; ?><a href="<?php echo zen_href_link(FILENAME_TARGETPAY_TRANSACTIONS, zen_get_all_get_params(array('action')) . 'action=checkstatus&transactionID=' . $selected_transaction->fields['transaction_id']); ?>">.</a></td>       
								                  		</tr>
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_TABLE_HEADING_ORDER_STATUS; ?></td>
								                    		<td class="dataTableContent"><?php echo (($selected_transaction->fields['orders_status']!='0') ? $selected_transaction->fields['orders_status_name'] : TARGETPAY_NAME_ZERO_STATUS_ORDER); ?></td>
								                  		</tr>
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_TABLE_HEADING_TRANSACTION_DATE_TIME; ?></td>
								                    		<td class="dataTableContent"><?php echo zen_datetime_short($selected_transaction->fields['datetimestamp']); ?></td>
								                  		</tr>
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_TABLE_HEADING_LAST_MODIFIED; ?></td>
								                    		<td class="dataTableContent"><?php echo zen_datetime_short($selected_transaction->fields['last_modified']); ?></td>
								                  		</tr>    
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_HEADING_CUSTOMER_ID; ?></td>
								                    		<td class="dataTableContent"><a target="_blank" href="<?php echo zen_href_link(FILENAME_CUSTOMERS,"selected_box=customers&cID=" . $selected_transaction->fields['customer_id'],'NONSSL')?>"><?php echo $selected_transaction->fields['customer_id']; ?></a></td>
								                  		</tr>
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_HEADING_TRANSACTION_AMOUNT; ?></td>
								                    		<td class="dataTableContent"><?php echo $selected_transaction->fields['currency']; ?> <?php echo sprintf("%.02f", round($selected_transaction->fields['amount']/100,2)); ?></td>
								                  		</tr>
								                  		<!-- 
								                 		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_HEADING_CUSTOMER_ACCOUNT; ?></td>
								                    		<td class="dataTableContent"><?php echo $selected_transaction->fields['consumer_account_number']; ?></td>
								                  		</tr>
								                  		 -->
								                  		 <!-- 
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_HEADING_CUSTOMER_NAME; ?></td>
								                    		<td class="dataTableContent"><?php echo $selected_transaction->fields['consumer_name']; ?></td>
								                  		</tr>
								                  		 -->
								                  		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
									                   		<td class="dataTableContent"><?php echo TARGETPAY_TABLE_HEADING_SUGGESTED_ACTION; ?></td>
									                    		<?php $res = getSuggestedAction($selected_transaction->fields['transaction_id'],$selected_transaction->fields['transaction_status'],$selected_transaction->fields['order_id'],$selected_transaction->fields['orders_status'], $selected_transaction->fields['session_id'], $selected_transaction_age);?>
									                    	<td class="dataTableContent"><a <?php echo $res['parameters']; ?>href="<?php echo $res['link']?>"><?php echo $res['text']; ?></a></td>
									                  	</tr>
									                  	<!-- 
								                 		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
								                    		<td class="dataTableContent"><?php echo TARGETPAY_HEADING_CUSTOMER_CITY; ?></td>
								                    		<td class="dataTableContent"><?php echo $selected_transaction->fields['consumer_city']; ?></td>
								                  		</tr>
								                  		 -->
<?php
}
?>
				                					</table>
				                				</td>
				              				</tr>
				            		</table>          
				            		<table>
				              			<tr>
				                			<td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
				              			</tr>
				              			<tr class="dataTableHeadingRow">
				                			<td class="dataTableHeadingContent"><?php echo TARGETPAY_HEADING_EXPLANATION; ?></td>
				              			</tr>
				              			<tr>
				                			<td class="dataTableContent"><?php echo TARGETPAY_TEXT_EXPLANATION; ?></td>
				              			</tr>
				            		</table>
				            	</td>
				          	</tr>
				        </table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<div id="issuerlist">
<h4>Lijst met banken (uit de database)</h4>
<?php
$targetpay_directorylist = targetpay_get_directorylist();

if($targetpay_directorylist)
{
?>
	<ul>
<?php
		foreach($targetpay_directorylist AS $bankObj) 
		{
			echo '<li>' . $bankObj->issuerName . ' (id: ' . $bankObj->issuerID . ', issuerlist: ' . $bankObj->issuerList . ')</li>';
		}
?>
</ul>
<?php
	}
	else
	{
		echo '<span style="color: red">De lijst met banken is op dit moment leeg, gebruik de TargetPay iDEAL betaalmodule (als klant) om een nieuwe lijst met banken op te halen van de TargetPay iDEAL server.</span>';
	}
?>
</div>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
