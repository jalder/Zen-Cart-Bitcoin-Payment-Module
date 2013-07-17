<?php
/**
 * @package admin
 */

  require('includes/application_top.php');

 

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
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
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onLoad="SetFocus(), init();">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<div id="bitcoin_accounts" style="margin-left: 5%;">
<?php 
    	require_once '../includes/modules/payment/bitcoin/jsonRPCClient.php';

		$bitcoin = new jsonRPCClient('http://'.MODULE_PAYMENT_BITCOIN_LOGIN.':'.MODULE_PAYMENT_BITCOIN_PASSWORD.'@'.MODULE_PAYMENT_BITCOIN_HOST.'/'); 
		
		?>
		
		<h1 class="pageheading">Bitcoin Accounts</h1>
		<table border="0">
		<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" align="center">Account</td><td class="dataTableHeadingContent">Address</td><td class="dataTableHeadingContent">Balance</td></tr>
		
		<?php
		try {
			$bitcoin->getinfo();
		} catch (Exception $e) {
			echo 'Error: Bitcoin server is down.  Please email system administrator.';
			$down = true;
		}
		if(!$down){
			global $db;
			$accounts = $bitcoin->listaccounts();
			//print_r($accounts);
			$count = 0;
			foreach($accounts as $a=>$t){
				//if($a!==''){
					$bc = $bitcoin->getaddressesbyaccount($a);
					print('<tr><td align="left">'.$a.'</td><td border="1px">');
					foreach($bc as $b){
						$v = $bitcoin->getreceivedbyaddress($b);
						print($b.'<br />');
						$sql = 'SELECT * FROM '.TABLE_ORDERS_STATUS_HISTORY.' AS osh LEFT JOIN '.TABLE_ORDERS_STATUS.' AS os ON os.orders_status_id = osh.orders_status_id WHERE os.orders_status_name = "'.Pending.'" AND osh.comments LIKE "%'.$b.'%"';
						$result = $db->Execute($sql);
						if ($result->RecordCount() > 0) {
						  while (!$result->EOF) {
						  	$sql = 'SELECT * FROM '.TABLE_ORDERS.' LEFT JOIN '.TABLE_ORDERS_STATUS.' on orders_status_id WHERE orders_id = '.$result->fields['orders_id'].' AND orders_status = "1"';
						  	$order = $db->Execute($sql);
						  	
						    echo '<a href="'.zen_href_link('orders.php?page=1&oID='.$result->fields['orders_id'].'&action=edit', '', 'NONSSL').'">Order '.$result->fields['orders_id'].'</a> | Due '.($order->fields['order_total'] * $order->fields['currency_value']).' BTC | Received '.$v.' BTC  | '.$result->fields['orders_status_name'].'<br />';
						    $result->MoveNext();
						  }
						}
						
						$count++;
					}
					print('</td><td>'.$t.'BTC</td></tr>');
				//}
			}
		}
		?>
		</table>
		<?php echo "Count: ".$count;

		?>
		
</div>
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
