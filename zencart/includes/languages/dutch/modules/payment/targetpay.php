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
  define('MODULE_PAYMENT_TARGETPAY_TEXT_DESCRIPTION', 'iDeal via TargetPay is het ideale online betalingssysteem in Nederland: snel, veilig en eenvoudig.<br/><a href="https://www.targetpay.com/signup?p_actiecode=YM3R2A">Vraag hier uw gratis TargetPay account aan</a>');

  define('MODULE_PAYMENT_TARGETPAY_TEXT_ISSUER_SELECTION', 'Kies uw bank...');
  define('MODULE_PAYMENT_TARGETPAY_TEXT_ISSUER_SELECTION_SEPERATOR', '---Overige banken---');
  define('MODULE_PAYMENT_TARGETPAY_TEXT_ORDERED_PRODUCTS', 'Bestelling: ');
  define('MODULE_PAYMENT_TARGETPAY_TEXT_INFO', '&nbsp;<span>Betaal veilig en snel via het betaalvenster van uw eigen bank.</span>');

  define('MODULE_PAYMENT_TARGETPAY_EXPRESS_TEXT', '<h3 id="iDealExpressText">Klik a.u.b. na betaling bij uw bank op "Volgende" zodat u terugkeert op onze site en uw order direct verwerkt kan worden!</h3>');
  define('MODULE_PAYMENT_IDEAL_EXPRESS_TEXT', '<h3 id="iDealExpressText">Klik a.u.b. na betaling bij uw bank op "Volgende" zodat u terugkeert op onze site en uw order direct verwerkt kan worden!</h3>');

  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_ERROR_OCCURRED_PROCESSING','Er is een fout opgetreden bij het verwerken van uw iDEAL transactie. Kies opnieuw een betaalwijze.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_ERROR_OCCURRED_STATUS_REQUEST','Er is een fout opgetreden bij het ophalen van de status van uw iDEAL transactie. Controleer of de transactie is uitgevoerd via internetbankieren van uw bank en neem vervolgens contact op met de webwinkel.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_NO_ISSUER_SELECTED', 'Er is geen bank geselecteerd, kies een bank of een andere betaalwijze.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_CANCELLED', 'De transactie werd geannuleerd, kies opnieuw een betaalwijze.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_EXPIRED', 'De transactie is verlopen, kies opnieuw een betaalwijze.');
  
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_INCOMPLETE', 'Door een storing is de status van uw betaling onbekend. Indien u nog niet heeft betaald dan verzoeken wij u nu opnieuw een betaalwijze te kiezen. Heeft u reeds heeft betaald dan zal uw bestelling spoedig door ons worden voltooid.');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRASACTION_FAILED', '[Transactie mislukt]&nbsp;' . MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_INCOMPLETE);
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_OPEN', '[Transactie open]&nbsp;' . MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_INCOMPLETE);
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_UNKNOWN_STATUS', '[Transactiestatus onbekend]&nbsp;' . MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_TRANSACTION_INCOMPLETE);
  define('MODULE_PAYMENT_TARGETPAY_ERROR_AMOUNT_TO_LOW', 'Het bedrag is te laag voor deze betaalmethode');
  define('MODULE_PAYMENT_TARGETPAY_ERROR_TEXT_NO_TRANSACTION_ID', 'Er werd geen transactienummer gevonden.');
  
