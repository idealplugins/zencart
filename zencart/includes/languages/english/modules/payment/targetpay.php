<?php
/**
 * TargetPay Payment Module for osCommerce
 *
 * @copyright Copyright 2013-2014 Yellow Melon
 * @copyright Portions Copyright 2013 Paul Mathot
 * @copyright Portions Copyright 2003 osCommerce
 * @license see LICENSE.TXT
 */

  define('MODULE_PAYMENT_TARGETPAY_TEXT_TITLE', 'TargetPay iDEAL');
  define('MODULE_PAYMENT_TARGETPAY_TEXT_DESCRIPTION', 'iDEAL via TargetPay is the ideal payment method via the Dutch banks: fast, safe, and simple.<br/><a href="https://www.targetpay.com/signup?p_actiecode=YM3R2A">Get a TargetPay account for free</a>');

  define('MODULE_PAYMENT_TARGETPAY_TEXT_ISSUER_SELECTION', 'Choose your bank...');
  define('MODULE_PAYMENT_TARGETPAY_TEXT_ISSUER_SELECTION_SEPERATOR', '---Other banks---');
  define('MODULE_PAYMENT_TARGETPAY_TEXT_ORDERED_PRODUCTS', 'Order: ');
  define('MODULE_PAYMENT_TARGETPAY_TEXT_INFO', 'Safe online payment via the Dutch banks.');

  define('MODULE_PAYMENT_TARGETPAY_EXPRESS_TEXT', '<h3 id="iDealExpressText">After payment in online banking, please click Next to get back to the store and we can process your order!</h3>');
  define('MODULE_PAYMENT_IDEAL_EXPRESS_TEXT', '<h3 id="iDealExpressText">After payment in online banking, please click Next to get back to the store and we can process your order!</h3>');

  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_ERROR_OCCURRED_PROCESSING','An error occurred while processing your iDEAL transaction. Please select a payment method.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_ERROR_OCCURRED_STATUS_REQUEST','An error occurred while confirming the status of your iDEAL transaction. Please check whether the transaction has been completed via your online banking system and then contact the web store.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_NO_ISSUER_SELECTED', 'No bank was selected; please select a bank or another payment method.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_CANCELLED', 'The transaction was cancelled; please select a payment method.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_EXPIRED', 'The transaction has expired; please select a payment method.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRASACTION_FAILED', 'The transaction failed; please select a payment method.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_UNKNOWN_STATUS', 'The transaction failed; please select a payment method.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_AMOUNT_TO_LOW', 'The amount is too low for this paymetn type');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_NO_TRANSACTION_ID', 'No transaction ID was found.');
 