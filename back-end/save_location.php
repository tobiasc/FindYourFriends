<?php
include_once 'functions.php';

if(verifyUser($_REQUEST['b'], $_REQUEST['a'])){
	$id = $_REQUEST['a'];

	$me = json_decode(file_get_contents('https://graph.facebook.com/'.$id));
	if($_REQUEST['description'] == 'Describe what you\'re doing'){
		$_REQUEST['description'] = '';
	}

	if($_REQUEST['post_to_wall'] == '1'){
		$m = new Mongo();
		$db = $m->findyourfriends_;
		$collection = $db->users;
		$cursor = $collection->find(array('id' => $id));
		foreach($cursor as $user){	
			$fields_string = '';
			$url = 'https://graph.facebook.com/'.$id.'/feed';
			$time_text = '';
			if($_REQUEST['hours'] > 0){				
				$time_text .= ($_REQUEST['hours'] > 1)?$_REQUEST['hours'].' hours ':$_REQUEST['hours'].' hour ';
				if($_REQUEST['minutes'] > 0){
					$time_text .= '& ';
				}
			}
			if($_REQUEST['minutes'] > 0){
				$time_text .= ($_REQUEST['minutes'] > 1)?$_REQUEST['minutes'].' minutes':$_REQUEST['minutes'].' minute';
			}
			$name_text = ($_REQUEST['hours'] > 0 || $_REQUEST['minutes'] > 0)?'Se where '.$me->first_name.' will be in '.$time_text:'Se where '.$me->first_name.' is right now!';
			$fields = array(
				'access_token'=>$user['access_token'],
				'name'=>urlencode($name_text),
				'message'=>urlencode(utf8_encode($_REQUEST['description'])),
				'caption'=>'Find your friends, see where they are on a map, and check-in to let your friends find you',
				'link'=>'http://apps.facebook.com/findyourfriends_/?u='.$id,
				'picture'=>'http://test.ing-site.com/facebook/findyourfriends_/includes/map_small.jpg'
			);
			foreach($fields as $key=>$value) { 
				$fields_string .= $key.'='.$value.'&'; 
			}
			rtrim($fields_string,'&');

			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POST,count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

			//execute post
			$result = curl_exec($ch);

			//close connection
			curl_close($ch);
		}
	}

	if(verify_request($_REQUEST['lat'], 'numeric') && verify_request($_REQUEST['long'], 'numeric')){
		$m = new Mongo();
		$db = $m->findyourfriends_;
		$collection = $db->checkins;
		$time = time() + ($_REQUEST['hours']*60*60) + ($_REQUEST['minutes']*60);

		$key = array('id' => $id);
		$obj = array('id' => $id, 'name' => $me->name, 'source' => 'Find Your Friends', 'lat' => $_REQUEST['lat'], 'long' => $_REQUEST['long'], 'time' => $time, 'timestamp' => time(), 'description' => utf8_encode($_REQUEST['description']));
		$collection->update($key, $obj, array('upsert' => true)); // upsert... sweet!
	}
}
?>
