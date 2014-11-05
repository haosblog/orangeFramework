<?php

class L_Oauth{
	
	function __construct(){
		include LIB_PATH.'/oauth2/PDOOAuth2.php';
	}
	
	public function authorize() {
		$oauth = new PDOOAuth2();
		
		if ($_GET) {
		  $oauth->finishClientAuthorization(true, $_GET);
		}
	}
	
	public function token(){
		$oauth = new PDOOAuth2();
		$oauth->grantAccessToken();
	}
}

?>