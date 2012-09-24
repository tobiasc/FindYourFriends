<?php
include_once 'functions.php';

if(verifyUser($_REQUEST['b'], $_REQUEST['a'])){
	$id = $_REQUEST['a'];

	$m = new Mongo();
	$db = $m->findyourfriends_;
	$collection = $db->users;
	$cursor = $collection->find(array('id' => $id));
	foreach($cursor as $user){	
		$friends_fb = json_decode(file_get_contents('https://graph.facebook.com/'.$id.'/friends?access_token='.$user['access_token']));
	}

	$friends = array();
	$friends_loc = array();
	foreach($friends_fb as $data => $array){
		foreach($array as $key => $friend){
			$friends[] = $friend->id;
		}
	}

	$m = new Mongo();
	$db = $m->findyourfriends_;
	$collection = $db->checkins;

	$cursor = $collection->find(array('id' => array('$in' => $friends)));
	$cur_time = time();
	foreach ($cursor as $obj) {
		$time = $cur_time - $obj['time'];
		if($time > $_REQUEST['days']*86400 && $_REQUEST['days'] > 0){
			continue;
		} else if($time < -1*60*60*24*30){
			$days = round($time / (60*60*24));
			$word = ($days == 1)?'day':'days';
			$time_text = 'In '.$days.' '.$word;
		} else if($time < -1*60*60*24){
			$hours = round($time / (60*60));
			$word = ($hours == 1)?'hour':'hours';
			$time_text = 'In '.$hours.' '.$word;
		} else if($time < -1*60*60){
			$minutes = round($time / 60);
			$word = ($minutes == 1)?'minute':'minutes';
			$time_text = 'In '.$minutes.' '.$word;
		} else if($time < -1*60){
			$time_text = 'In a minute';
		} else if($time < 60){
			$time_text = 'Less than a minute ago';
		} else if($time < 60*60){
			$minutes = round($time / 60);
			$word = ($minutes == 1)?'minute':'minutes';
			$time_text = $minutes.' '.$word.' ago';
		} else if($time < 60*60*24){
			$hours = round($time / (60*60));
			$word = ($hours == 1)?'hour':'hours';
			$time_text = $hours.' '.$word.' ago';
		} else if($time < 60*60*24*30){
			$days = round($time / (60*60*24));
			$word = ($days == 1)?'day':'days';
			$time_text = $days.' '.$word.' ago';
		}
		$friends_loc[$obj['id']] = array('id' => $obj['id'], 'lat' => $obj['lat'], 'description' => utf8_decode($obj['description']),
			'lng' => $obj['long'], 'name' => utf8_decode($obj['name']), 'time_text' => $time_text, 'time' => $time);
	}
	echo json_encode($friends_loc);
}
?>
