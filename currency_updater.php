#!/usr/bin/php
<?php
 

#Script patched together from snippets of code I found on the web. Adrian Golser, Sept. 2012.
#Enter defining data at beginning, make sure btce-api returns correct value and set up a cron-job to execute
#according to preferences.

define('BTCEXCHANGE', 'btce'); 	// Wich Exchange are we using? 
				// For MtGox use code 'mtgox'.
				// For BTC-E use code 'btce'.



# Set this to the physical path of your installation.
# Make sure to include the trailing "/"
define('PATH_TO_STORE_ROOT','/var/zpanel/hostdata/megax/public_html/electro_megaxnetwork_com/');


#Settings for rate calculation: (typical btce-api-output: {"ticker":{"high":8.79,"low":8.40145,"avg":8.583123374,"vwap":8.56141034,"vol":2342,"last_all":8.679,"last_local":8.679,"last":8.679,"buy":8.59688,"sell":8.679}}
define('WHICH_VALUE','avg');

define('DEFAULT_SYMBOL','USD');

if (BTCEXCHANGE != 'btce'){ // For BTC-E we get the fee from the api so this is not needed.
	define('EXCHANGE_FEE','02'); // Exchange fee you add to your rate. Example: write 02 for 2%
	
	#API-Settings. Create from your mtgox-account.
	define('MTGOX_KEY','');
	define('MTGOX_SECRET','');
}

/////////////////////



// Get Zen Cart configuration data
require_once(PATH_TO_STORE_ROOT . 'includes/configure.php');
require_once(PATH_TO_STORE_ROOT . 'includes/database_tables.php');


//get ticker data from btce. Code is from bitcoin wiki. Output is 2d-array
function exchange_query($path, array $req = array()) {
  // API settings

 
	// generate a nonce as microtime, with as-string handling to avoid problems with 32bits systems
	$mt = explode(' ', microtime());
	$req['nonce'] = $mt[1].substr($mt[0], 2, 6);
 
	// generate the POST data string
	$post_data = http_build_query($req, '', '&');
 
	if (BTCEXCHANGE != 'btce'){
		// generate the extra headers
		$headers = array(
			'Rest-Key: '.MTGOX,
			'Rest-Sign: '.base64_encode(hash_hmac('sha512', $post_data, base64_decode(MTGOX_SECRET), true)),
		);
	}

	// our curl handle (initialize if required)
	static $ch = null;
	if (is_null($ch)) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MtGox PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	}
	
	if (BTCEXCHANGE == 'btce'){ curl_setopt($ch, CURLOPT_URL, 'https://btc-e.com/api/2/'.$path);}
	if (BTCEXCHANGE == 'mtgox'){ curl_setopt($ch, CURLOPT_URL, 'https://data.mtgox.com/api/'.$path);}
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	if (BTCEXCHANGE != 'btce'){curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);}
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
	// run the query
	$res = curl_exec($ch);
	if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
	$dec = json_decode($res, true);
	if (!$dec) throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
	return $dec;
}
 

//bit of math
if (BTCEXCHANGE == 'mtgox'){
	define('EFEE','1.'.EXCHANGE_FEE); // No need to change this...
	$exchange_array = exchange_query('0/ticker.php?Currency='.DEFAULT_SYMBOL);
}elseif (BTCEXCHANGE == 'btce'){
	$feeurl = 'https://btc-e.com/api/2/btc_'.strtolower(DEFAULT_SYMBOL).'/fee';
	$json = file_get_contents($feeurl); 
	$fee = json_decode($json);
	define('EFEE', '1.'.($fee->trade * 100)); 
	$exchange_array = exchange_query('btc_'.strtolower(DEFAULT_SYMBOL).'/ticker');
}
$newrate = (1 / (($exchange_array["ticker"][WHICH_VALUE]) * EFEE));

//uncomment for testing
return (var_dump((float)($exchange_array["ticker"][WHICH_VALUE])));
return (var_dump($newrate));


//Time to punch this into the database

$mysqli = new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$mysqli->select_db(DB_DATABASE) or die("Database not avalable");
$mysqli->query("update ".TABLE_CURRENCIES. " set value='$newrate', last_updated=now() where code='BTC'");
$mysqli->close();

?>
