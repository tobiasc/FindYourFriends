<?php
function verify_request($string, $type = 'string'){
	if($string != ''){
		if($type == 'numeric' && is_numeric($string)){
			return true;
		} else {
			return true;
		}
	}
	return false;
}

function verifyUser($hash, $id){
	if(is_numeric($id)){
		$time = time();
		$user = json_decode(file_get_contents('https://graph.facebook.com/'.$id));

		if($hash == sha1('him'.$id.'self'.$user->username) || $hash == sha1('him'.$id.'self'.$user->username)
			|| $hash == sha1('him'.$id.'self'.$user->username) || $hash == sha1('him'.$id.'self'.$user->username)
			|| $hash == sha1('him'.$id.'self'.$user->username) || $hash == sha1('him'.$id.'self'.$user->username)){
			return true;
		} else {
			return false;
		}
	}
}
?>
