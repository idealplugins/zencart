<?php

/*
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
$zsn = preg_replace("/[^A-Za-z0-9 ]/", '', $_GET["zsn"]); // Zend Session Name
$zenid = preg_replace("/[^A-Za-z0-9 ]/", '', $_GET["zenid"]); // Zend Session ID
$trxid = preg_replace("/[^A-Za-z0-9 ]/", '', $_POST["trxid"]); // Transaction ID => Let op: dit is een POST
$method = preg_replace("/[^A-Za-z0-9 ]/", '', $_GET["method"]); // Payment Method ID

$url =	$scheme . 
		str_replace("targetpay_callback.php", "index.php", $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).
		"?main_page=checkout_process&trxid=".$trxid."&method=".$method."&action=process&".$zsn."=".$zenid;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, 1);
$result = curl_exec($ch);
curl_close($ch);
*/

echo "45000";