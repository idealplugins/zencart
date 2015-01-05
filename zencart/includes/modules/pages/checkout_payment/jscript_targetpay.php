<?php
/**

	TargetPay module class for Zencart
	(C) Copyright Yellow Melon B.V. 2013

*/
?>
<script type="text/javascript" language="javascript"><!--
function check_targetpay() {
  for (var j = 0; j < document.checkout_payment.payment.length; j++) {

    if (document.checkout_payment.payment[j].value == 'targetpay') {
      document.checkout_payment.payment[j].checked = true;
      // alert('true');
    }else{
      document.checkout_payment.payment[j].checked = false;
    }
  }
}
//--></script>