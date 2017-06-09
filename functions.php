<?php
// This version looks to see if there is a patron block called "PROXY" set in Alma. This needs to be added to the Alma blocks
#Check Alma API for Patron Type and Record Status
function auth_alma($user) {
	global $key;
	global $allowable_patrons;
	global $faculty;
	
	$ch = curl_init();
		$url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}';
		$templateParamNames = array('{user_id}');
		$templateParamValues = array(urlencode($user));
		$url = str_replace($templateParamNames, $templateParamValues, $url);
		$queryParams = '?' . urlencode('user_id_type') . '=' . urlencode('all_unique') . '&' . urlencode('view') . '=' . urlencode('full') . '&' . urlencode('expand') . '=' . urlencode('none') . '&' . urlencode('apikey') . '=' . urlencode($key);
		curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	
		$response = curl_exec($ch);
	curl_close($ch);
	
//parse the XML response
	$group_auth = $status_auth = $authenticate = 1;
	$patron_type = $status = "";
	$xmlObj = simplexml_load_string($response);
	$patron_type = $xmlObj->user_group;
	$status = $xmlObj->status;
	$block = '';
	foreach ($xmlObj->user_blocks->user_block as $userblocks) { 
	$block .= ' '.$userblocks->block_description; // list all of the user blocks in the Alma record
	}
	
	//Check if Allowed Patron Type
		if (in_array($patron_type, $allowable_patrons)) {
  			$group_auth = "0";
		} else {
  			$group_auth = "1";
		}
	//Check if Record Status is active or expired
		if ($status == "ACTIVE") {
  			$status_auth = "0";
		} else {
  			$status_auth = "2";
		}
	//Check the word "Proxy" exists in the list of blocks
		if (strpos($block, 'PROXY') !== false)
  			$proxy_auth = "4";
		else
  			$proxy_auth = "0";
	//Define Faculty Group for logs
	global $groups;
	$groups = "ezproxy_group=Default";
	
	if (in_array($patron_type, $faculty)) {
 		$groups = $groups . "+Faculty";
	}

//Return authentication
	$authenticate = $group_auth + $status_auth + $proxy_auth; // Add the Proxy to the error count if it exists
	return $authenticate;
//End Alma API Function
}

//Check LDAP
function auth_ldap($username, $upasswd) {
	require('config.php');
	$found=0;
	$ds = ldap_connect($ldap_server, $ldap_port) or die("Could not connect to $ldaphost");
	if ($ds) {
 		$binddn = "uid=" .$username ."," . $base; 
 		$ldapbind = @ldap_bind($ds,$binddn, $upasswd);
 
		//check if ldap was sucessful, if fail return 3
		if ($ldapbind) {
    		$found=0;
		} else {
    		$found=3;
		}
		}
	return $found;
}

?>
