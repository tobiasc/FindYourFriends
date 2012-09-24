<?php
if(isset($_REQUEST['ip']) && $_REQUEST['ip'] != ''){
	$geolocation = file_get_contents('http://api.ipinfodb.com/v3/ip-city/?key=b328a71a3a996e2c882f205b93f6d9ea4b9bd349a9cda7b2cdcacd31de0d88aa&ip='.$_REQUEST['ip']);
	$array = explode(';', $geolocation);
	if($array[0] == 'OK'){
		echo json_encode(array($array[8], $array[9]));
	}
}
?>
