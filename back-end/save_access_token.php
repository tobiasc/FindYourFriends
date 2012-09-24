<?php
include_once 'functions.php';

if(verifyUser($_REQUEST['b'], $_REQUEST['a'])){
	$id = $_REQUEST['a'];
	$access_token = $_REQUEST['access_token'];

	// update access tokens so we can access user data in ajax calls
	$m = new Mongo();
	$db = $m->findyourfriends_;
	$collection = $db->users;
	$key = array('id' => $id);
	$obj = array('id' => $id, 'access_token' => $access_token);
	$collection->update($key, $obj, array('upsert' => true)); // upsert... sweet!
}
?>
