<?php 
//Execute hwoami /fqdn command to get domain user
if($whoami = `whoami /fqdn`){

	//Check domain user (error if no domain user)
	if( ! stristr($whoami, 'ERROR:')){

		$parts = explode(',', $whoami);
		$fqdn = array();
		foreach($parts as $part){
			list($key,$value) = explode('=', $part);
			if(isset($fqdn[$key])){
				$fqdn[$key] = $fqdn[$key] . '.' .$value;
			}else{
				$fqdn[$key] = $value;
			}
		}

		$remoteuser = $fqdn['CN'].'@'.strtoupper($fqdn['DC']);

		$_SERVER['REMOTE_USER'] = $remoteuser;
	}
}
?>