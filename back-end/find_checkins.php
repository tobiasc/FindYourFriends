<?php
include_once 'functions.php';

function update_location($lat, $long, $time, $id, $friends_recent, $place_name, $name, $collection){
	$time = strtotime($time);
	$description = 'Checked in at '.$place_name;
	if(!in_array($id, $friends_recent) && verify_request($lat, 'numeric') && verify_request($long, 'numeric') && $time > time()-60*60*24*30){
		$key = array('id' => $value_->id, 'time' => array('$lte' => $time));
		$obj = array('id' => $id, 'source' => 'Facebook', 'name' => utf8_encode($name), 'lat' => $lat, 'long' => $long, 'time' => $time, 'description' => utf8_encode($description), 'timestamp' => time());
		$collection->update($key, $obj, array('upsert' => true)); // upsert... sweet!
	}
}

if(verifyUser($_REQUEST['b'], $_REQUEST['a'])){
	$id = $_REQUEST['a'];

	$m = new Mongo();
	$db = $m->findyourfriends_;
	$collection = $db->users;
	$cursor = $collection->find(array('id' => $id));
	foreach($cursor as $user){	
		$access_token = $user['access_token'];
	}
	$friends = json_decode(file_get_contents('https://graph.facebook.com/'.$id.'/friends?access_token='.$access_token));
	// find existing friends, we don't need to update those that have been updated recently
	$friends_existing = array();
	$friends_recent = array();
	foreach($friends as $data => $array){
		foreach($array as $key => $friend){
			$friends_existing[] = $friend->id;
		}
	}

	// determine how many friends this user has & make list of users updated within the last 6 hours
	$friends_num = 0;
	$collection = $db->checkins;
	$cursor = $collection->find(array('id' => array('$in' => $friends)));
	foreach ($cursor as $obj) {
		if(strtotime($obj['timestamp']) > time()*60*60*6){
			$friends_recent = $obj['id'];
		}
		$friends_num++;
	}

	// first go through friends already in db in descending order by who checkin in (last ones gets checked first)
	$number_of_requests = 0;
	$updated_friends = array();
	$collection = $db->checkins;
	$cursor = $collection->find(array('id' => array('$in' => $friends)));
	$cursor->sort(array('time' => 1));
	foreach ($cursor as $obj) {
		$checkins = json_decode(file_get_contents('https://graph.facebook.com/'.$value_->id.'/checkins?access_token='.$access_token));			
		if(isset($checkins->data[0])){
			$checkin = $checkins->data[0];
			update_location($checkin->place->location->latitude, $checkin->place->location->longitude, $checkin->created_time, $value_->id, $friends_recent, $checkin->place->name, $value_->name, $collection);
		}
		$updated_friends[$obj['id']] = 1;
		$number_of_requests++;
		if($number_of_requests > 100){
			sleep(1);
		}
	}

	// then go through all friends not in the db
	foreach($friends as $key => $value){
		foreach($value as $key_ => $value_){
			if(!isset($updated_friends[$value_->id])){
				$checkins = json_decode(file_get_contents('https://graph.facebook.com/'.$value_->id.'/checkins?access_token='.$access_token));		
				if(isset($checkins->data[0])){
					$checkin = $checkins->data[0];
					update_location($checkin->place->location->latitude, $checkin->place->location->longitude, $checkin->created_time, $value_->id, $friends_recent, $checkin->place->name, $value_->name, $collection);
				}
				$number_of_requests++;
				if(($friends_num < 5 && $number_of_requests > 100) || ($friends_num >= 5 && $number_of_requests > 10)){
					sleep(1);
				}
			}
		}
	}
}
?>
