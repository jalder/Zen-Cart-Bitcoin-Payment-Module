<?php
/**
 *
 * Bitcoin Payment Module
 * 
 * @package paymentMethod
 *
 *  Author: Jalder (https://github.com/jalder/Zen-Cart-Bitcoin-Payment-Module/)
 *  Moddified by:  MasterX1582 (https://github.com/MasterX1582/Zen-Cart-Bitcoin-Payment-Module/)
 *  Donations: 1JBKYhNvF1B8eLEcCUq3jw8wvrzDCPCGiB
 *
 **/
 
  class bitcoin {
    var $code, $title, $description, $enabled, $payment;

// class constructor
    function bitcoin() {
      global $order;
      $this->code = 'bitcoin';
      $this->title = MODULE_PAYMENT_BITCOIN_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_BITCOIN_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_BITCOIN_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_BITCOIN_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_BITCOIN_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_BITCOIN_ORDER_STATUS_ID;
        $payment='bitcoin';
      } else {
        if ($payment=='bitcoin') {
          $payment='';
        }
      }

      if (is_object($order)) $this->update_status();

      $this->email_footer = MODULE_PAYMENT_BITCOIN_TEXT_EMAIL_FOOTER;
    }

// class methods
    function update_status() {
      global $db;
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_BITCOIN_ZONE > 0) ) {
        $check_flag = false;
        $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_BITCOIN_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while (!$check->EOF) {
          if ($check->fields['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
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

    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
    	//Here we will generate a new payment address and any other related tasks
    	global $order;
    	
    	require_once 'bitcoin/jsonRPCClient.php';

		$bitcoin = new jsonRPCClient('http://'.MODULE_PAYMENT_BITCOIN_LOGIN.':'.MODULE_PAYMENT_BITCOIN_PASSWORD.'@'.MODULE_PAYMENT_BITCOIN_HOST.'/'); 

		try {
			$bitcoin->getinfo();
		} catch (Exception $e) {
			$confirmation = array('title'=>'Error: Bitcoin server is down.  Please email system administrator regarding your order after confirmation.');
			return $confirmation;
		}
		
		$address = $bitcoin->getaccountaddress($order->customer['email_address'].'-'.session_id());
		$confirmation = array('title' => '');
    	$confirmation['fields'] = array(
    		array('title'=>'Payment Address','field'=>'<div><br />Send Payments to:<hr> '.$address.'</div> <hr>'),
    		//array('title'=>'Bitcoin js Client','field'=>'<div><br />Host: <input type="text" name="host" /><br />User: <input type="text" name="user" /><br />Pass: <input type="password" name="pass" /><br /><input type="submit" value="Authenticate" /></div>'),
    	);
		
      return $confirmation;
    }

    function process_button() {
      return false;
    }

    function before_process() {
    	global $insert_id, $db, $order;
    	$address = $order->customer['email_address'].'-'.session_id();

		require_once 'bitcoin/jsonRPCClient.php';

		$bitcoin = new jsonRPCClient('http://'.MODULE_PAYMENT_BITCOIN_LOGIN.':'.MODULE_PAYMENT_BITCOIN_PASSWORD.'@'.MODULE_PAYMENT_BITCOIN_HOST.'/'); 

    	try {
			$bitcoin->getinfo();
		} catch (Exception $e) {
			$confirmation = array('title'=>'Error: Bitcoin server is down.  Please email system administrator regarding your order after confirmation.');
			return $confirmation;
		}
		
		$address = $bitcoin->getaccountaddress($address);
		$order->info['comments'] .= ' | Payment Address: '.$address.' | ';
		
      return false;
    }

    function after_process() {
      return false;
    }

    function get_error() {
      return false;
    }

    function check() {
      global $db;
      if (!isset($this->_check)) {
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_BITCOIN_STATUS'");
        $this->_check = $check_query->RecordCount();
      }
      return $this->_check;
    }

    function install() {
      global $db, $messageStack;
      if (defined('MODULE_PAYMENT_BITCOIN_STATUS')) {
        $messageStack->add_session('Bitcoin module already installed.', 'error');
        zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=bitcoin', 'NONSSL'));
        return 'failed';
      }
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Bitcoin Module', 'MODULE_PAYMENT_BITCOIN_STATUS', 'True', 'Do you want to accept Bitcoin payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now());");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Host Address', 'MODULE_PAYMENT_BITCOIN_HOST', 'localhost:8332', 'The host address for Bitcoin RPC', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Username', 'MODULE_PAYMENT_BITCOIN_LOGIN', 'testing', 'The Username for Bitcoin RPC', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, set_function, use_function) values ('Password', 'MODULE_PAYMENT_BITCOIN_PASSWORD', '', 'The Password for Bitcoin RPC', '6', '25', now(), 'zen_cfg_password_input(', 'zen_cfg_password_display')");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_BITCOIN_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_BITCOIN_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_BITCOIN_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    }

    function remove() {
      global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_BITCOIN_STATUS', 'MODULE_PAYMENT_BITCOIN_ZONE', 'MODULE_PAYMENT_BITCOIN_ORDER_STATUS_ID', 'MODULE_PAYMENT_BITCOIN_SORT_ORDER', 'MODULE_PAYMENT_BITCOIN_LOGIN', 'MODULE_PAYMENT_BITCOIN_HOST', 'MODULE_PAYMENT_BITCOIN_PASSWORD');
    }
  }
 
