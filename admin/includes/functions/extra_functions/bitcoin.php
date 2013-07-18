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
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
} 

//----
// If the installation supports admin-page registration (i.e. v1.5.0 and later), then
// register the New Tools tool into the admin menu structure.
//
if (function_exists('zen_register_admin_page')) {
  if (!zen_page_key_exists('reportsBitcoinPayment')) {
    zen_register_admin_page('reportsBitcoinPayment', 'BOX_REPORTS_PAYMENT_BITCOIN', 'FILENAME_PAYMENT_BITCOIN','' , 'reports', 'Y', 20);
  }    
}
