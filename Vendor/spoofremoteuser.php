<?php 
if($username = `whoami`){
	if(stristr($username, '\\')){
		list($domain,$username) = explode('\\', $username);
	}
	if(!empty($username)){
		$_SERVER['REMOTE_USER']=trim($username);
	}	
}
?>