<?php
/**

	TargetPay module class for Zencart
	(C) Copyright Yellow Melon B.V. 2013

*/

// or use $autoLoadConfig[71][] for example? = right after init_sessions.php
  $autoLoadConfig[1000][] = array('autoType'=>'init_script',
                                 'loadFile'=> 'init_targetpay_repair.php');
?>