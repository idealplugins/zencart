<?php
/**
 * TargetPay Payment Module for osCommerce
 *
 * @copyright Copyright 2013-2014 Yellow Melon
 * @copyright Portions Copyright 2013 Paul Mathot
 * @copyright Portions Copyright 2003 osCommerce
 * @license see LICENSE.TXT
 */


	define('FILENAME_TARGETPAY', 'targetpay.php');
	define('HTTP_SERVER_TARGETPAY_ADMIN', '');
	define('MODULE_PAYMENT_TARGETPAY_ERROR_REPORTING', 0);
	define('MODULE_PAYMENT_TARGETPAY_MERCHANT_RETURN_URL', ((ENABLE_SSL == 'true') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG . 'targetpay_callback.php');
?>
