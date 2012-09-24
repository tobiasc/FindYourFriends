<?php
 
include_once 'includes/facebook.php';
include_once 'config.php';
 
function getRealIpAddr(){
	if (!empty($_SERVER['HTTP_CLIENT_IP'])){
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];	
	}
	return $ip;
}

$facebook = new Facebook(array(
	'appId'  => FACEBOOK_APP_ID,
	'secret' => FACEBOOK_SECRET_KEY,
	'cookie' => true,
	'domain' => 'ing-site.com'
));
 
$session = $facebook->getSession();
 
if (!$session) {
	$url = 'https://www.facebook.com/dialog/oauth?client_id='.FACEBOOK_APP_ID.'&redirect_uri='.FACEBOOK_CANVAS_URL.'&scope=publish_stream,user_checkins,friends_checkins';
	echo "<script type='text/javascript'>top.location.href = '$url';</script>";
 
} else {
	try {
		$uid = $facebook->getUser();
		$me = $facebook->api('/me');

		// first update access tokens so we can access user data in ajax calls
		$m = new Mongo();
		$db = $m->findyourfriends_;
		$collection = $db->users;
		$key = array('id' => $me['id']);
		$obj = array('id' => $me['id'], 'access_token' => $facebook->getAccessToken());
		$collection->update($key, $obj, array('upsert' => true)); // upsert... sweet!

		$you = (isset($_REQUEST['u']) && $_REQUEST['u'] == $me['id'])?'var you = true;':'var you = false;';
		$u = (isset($_REQUEST['u']))?'var uid = '.$_REQUEST['u'].';':'var uid = false;';
		$center = 'var center = false';
		if(isset($_REQUEST['u'])){
			$collection = $db->checkins;
			$cursor = $collection->find(array('id' => $_REQUEST['u']));
			foreach($cursor as $obj){	
				$center = 'var center = new google.maps.LatLng('.$obj['lat'].', '.$obj['long'].');';
			}
		}

		$hash = sha1('him'.$me['id'].'self'.$me['username']);
		$c = date('dYHmi', time()).$me['id'].$me['username'];

		// include jQuery
		echo '<script src="http://test.ing-site.com/facebook/findyourfriends_/includes/jquery-1.6.1.min.js" type="text/javascript"></script>';

		// include Google Maps
		echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=true&amp;key=ABQIAAAAYNrxZqPTuL_GZv4fRpfHnBQWyGb3wOm9-Mhw60aos1gc88CG3BQvIEYK-hFeGGsNOEDa3nSV-4heaQ" type="text/javascript"></script>';
		echo '<script>
			'.$you.'
			'.$u.'
			var uid_you = '.$uid.';			
			var map;
			var markersArray = [];
			var me_marker; 
			var me_infowindow;
			var open_infowindow;
			var friends = new Object;
			var first_time_u = true;
			var first_time_c = true;

			function saveLocation(){
				var post_to_wall = 0;
				if($("#post_to_wall").attr("checked")){
					post_to_wall = 1;
				}
				var minutes = 0;
				var hours = 0;
				if($("#check-in_when_soon").attr("checked")){
					minutes = $("#check-in_when_minutes").val();
					hours = $("#check-in_when_hours").val();
				}
				var description = $("#description_textarea").val().replace(/\r\n/i, " ").replace(/\r/i, " ").replace(/\n/i, " ");
				description = encodeURIComponent(description);
				sendRequest("http://test.ing-site.com/facebook/findyourfriends_/save_location.php?post_to_wall="+post_to_wall+"&minutes="+minutes+"&hours="+hours+"&description="+description+"&lat="+$("#lat").val()+"&long="+$("#long").val(), false);
				me_infowindow.close();
				show_friends();
			}

			function show_friends_request(){
				show_friends();
				sendRequest("http://test.ing-site.com/facebook/findyourfriends_/get_location.php?days="+$("#days").val(), add_friends);
				var t = setTimeout("show_friends_request()",5000);
			}

			function initialize() {
				'.$center.'
				if(!center){
					var geoLatlng = new google.maps.LatLng(0, 0);
				} else {
					var geoLatlng = center;
					check_in_toggle("close");
				}
				var myOptions = {
					zoom: 1,
					center: geoLatlng,
					zoomControl: true,
					zoomControlOptions: {
						style: google.maps.ZoomControlStyle.LARGE
					},
					mapTypeId: google.maps.MapTypeId.ROADMAP
				}
				map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
				me_marker = new google.maps.Marker({
					position: geoLatlng, 
					draggable: true,
					map: map,
					visible: true,
					icon: "http://test.ing-site.com/facebook/findyourfriends_/includes/you.png",
					title:"'.$me['name'].'"
				});
				var contentString = "<div><div style=\"float:left;\"><img src=\"http://graph.facebook.com/'.$me['id'].'/picture\"/></div><div style=\"float:left;margin-left:10px;\"><a href=\"https://www.facebook.com/profile.php?id='.$me['id'].'\" target=\"_blank\">'.$me['name'].'</a></div></div>";
				me_infowindow = new google.maps.InfoWindow({
					content: contentString
				});
				google.maps.event.addListener(me_marker, "click", function() {
					me_infowindow.open(map,me_marker);
				});
				google.maps.event.addListener(map, "rightclick", function(event) {
					var latlng = event.latLng;
					$("#lat").val(latlng.lat());
					$("#long").val(latlng.lng());
					me_marker.setPosition(latlng);
					check_in_toggle("open");
				});

				google.maps.event.addListener(me_marker, "dragend", function() {
					var newlatlng = me_marker.getPosition();
					$("#lat").val(newlatlng.lat());
					$("#long").val(newlatlng.lng());
					check_in_toggle("open");
				});
				var pLoc = false;

				if(!you){
					sendRequest("http://test.ing-site.com/facebook/findyourfriends_/ipinfodb.php?ip='.getRealIpAddr().'", setCenter);
					sendRequest("http://test.ing-site.com/facebook/findyourfriends_/find_checkins.php?t=0", show_friends_request);
					if (navigator.geolocation) {
						navigator.geolocation.getCurrentPosition(function (position) {
							setCenter(new Array(position.coords.latitude, position.coords.longitude));
							pLoc = true;
						});
					}		
				} else {
					$("#lat").val(center.lat());
					$("#long").val(center.lng());
					map.panTo(center);
					map.setZoom(14);					
				}
				function setCenter(array){
					if(!pLoc){
						$("#lat").val(array[0]);
						$("#long").val(array[1]);
						var geoLatlng = new google.maps.LatLng(array[0], array[1]);
						if(!center){
							map.panTo(geoLatlng);
						}
						map.setZoom(14);
						me_marker.setPosition(geoLatlng);
					}
				}
				show_friends_request();
			}
			  
			function loadScript() {
				var script = document.createElement("script");
				script.type = "text/javascript";
				script.src = "http://maps.google.com/maps/api/js?sensor=false&callback=initialize";
				document.body.appendChild(script);
			}
			  
			window.onload = loadScript;			

			function sendRequest(url, callback) {
				url += "&a='.$me['id'].'&b='.$hash.'&c='.'";
				$.getJSON(url, function(data) {
					if(callback){
						callback(data);
					}
				});
			}

			function add_friends(new_friends){
				var num_of_friends = 0;
				for (new_friend_id in new_friends){
					if(!(new_friend_id in friends)){
						friends[new_friend_id+""] = new_friends[new_friend_id];						
					}
					num_of_friends++;
				}
				if(num_of_friends > 0){
					$("#friends_update").html("");
				} else {
					$("#friends_update").html("We didn\'t find any of your friends in our database, so we\'ve gone across the road to ask facebook. It might take up to a minute, but hang tight your friends will appear as we find them :-) <img src=\"includes/wait.gif\">");
				}
				show_friends();
			}

			function show_friends(){
				// show friends that are within the specified time range, remove all others
				var time = $("#days").val()*24*60*60;
				var num_of_friends = 0;
				for (id in friends){
					if(friends[id]["time"] > time && friends[id]["marker_set"]){
						friends[id]["marker"].setMap(null);
						friends[id]["marker_set"] = false;
					} else if(friends[id]["time"] < time && !friends[id]["marker_set"]){
						insertMarker(friends[id]);
						friends[id]["marker_set"] = true;
					}
					num_of_friends++;
				}
				// zoom out to closest friend when the first friends load
				if(!uid && first_time_c && num_of_friends > 1){
					first_time_c = false;
					var nearest_friend = 1000;
					var lat = Number($("#lat").val());
					var lng = Number($("#long").val());
					for (id in friends){
						var distance = get_distance(friends[id]["lat"], friends[id]["lng"], lat, lng);
						if(nearest_friend > distance && id != uid_you){
							nearest_friend = distance;
						}
					}
					var sw_location = new google.maps.LatLng(lat-nearest_friend, lng-nearest_friend);
					var ne_location = new google.maps.LatLng(lat+nearest_friend, lng+nearest_friend);
					var bounds = new google.maps.LatLngBounds(sw_location, ne_location);
					map.fitBounds(bounds);
				}
			}

			function get_distance(t_lat, t_lng, lat, lng){
				return Math.sqrt(Math.pow(t_lat - lat, 2) + Math.pow(t_lng - lng,2));
			}

			function insertMarker(friend){
				var marker_location = new google.maps.LatLng(friend["lat"], friend["lng"]);
				var marker = new google.maps.Marker({
					position: marker_location,
					draggable: false,
					map: map,
					visible: true,
					animation: google.maps.Animation.DROP,
					icon: "http://test.ing-site.com/facebook/findyourfriends_/includes/friend.png",
					title:friend["name"]
				});
				var string = "<div><div style=\"float:left;\"><img src=\"http://graph.facebook.com/"+friend["id"]+"/picture\"/></div><div style=\"float:left;margin-left:10px;\"><a href=\"https://www.facebook.com/profile.php?id="+friend["id"]+"\" target=\"_blank\">"+friend["name"]+"</a><div style=\"font-size:10pt;\">"+friend["description"]+"<br>"+friend["time_text"]+"</div></div></div>";
				var infowindow = new google.maps.InfoWindow({
					content: string
				});
				google.maps.event.addListener(marker, "click", function() {
					if(open_infowindow){
						open_infowindow.close();
					}
					infowindow.open(map,marker);
					open_infowindow = infowindow;
					map.panTo(marker_location);
				});
				friends[friend["id"]]["marker"] = marker;
				if(uid && first_time_u && uid == friend["id"]){
					first_time_u = false;
					infowindow.open(map, marker);
				}
			}

			function check_in_toggle(action){
				if($("#check-in_box").css("display") == "block" && (action == "close" || action == "toggle")){
					$("#check-in_box").slideUp("slow");
					$("#check-in_top_box").css({"background":"url(http://test.ing-site.com/facebook/findyourfriends_/includes/arrow-asc.png) no-repeat"});
					$("#map_canvas").animate({height: 410}, "slow");
				} else if($("#check-in_box").css("display") == "none" && (action == "open" || action == "toggle")){
					$("#check-in_box").slideDown("slow");
					$("#check-in_top_box").css({"background":"url(http://test.ing-site.com/facebook/findyourfriends_/includes/arrow-desc.png) no-repeat"});
					$("#map_canvas").animate({height: 300}, "slow");
				}
			}

			function set_check_in_when(){
				$("#check-in_when_now").attr({"checked":false});
				$("#check-in_when_soon").attr({"checked":true});
			}

			function change_textarea(action){
				var text = "Describe what you\'re doing";
				if(action == "onfocus" && $("#description_textarea").val() == text){
					$("#description_textarea").css({"color":"#000000"});
					$("#description_textarea").val("");
				} else if(action == "onblur" && $("#description_textarea").val() == ""){
					$("#description_textarea").css({"color":"#999999"});
					$("#description_textarea").val(text);
				}

			}

			</script>';

		echo '<div style="width:720px;overflow:hidden;">';

		// headline & dropdown time-select box
		echo '<div style="font-family:sans-serif;float:left;margin-bottom:10px;"><span style="font-size:20px;font-weight:bold;">Find your friends</span><span style="font-size:13px;"> ...and check-in to let your friends find you :-)</span></div>';
		echo '<div style="float:right;font-family:sans-serif;font-size:13px;">Activity from: <select id="days" onchange="show_friends_request();">
			<option value="0">The Future</option>
			<option value="1">Today</option>
			<option value="7">Last Week</option>
			<option value="14">Last 2 weeks</option>
			<option value="30" selected="selected">Last Month</option>
			</select></div>';

		// black box on top of map
		echo '<style>
			#check-in_top_box_span:hover {text-decoration: underline;cursor:pointer;}
			.check-in_header {font-size:20px;}
			</style>';
		echo '<div style="background-color:#000000;color:#ffffff;font-family:sans-serif;font-size:13px;clear:both;width:720px;padding:5px;">';
		echo '<div id="check-in_top_box" style="background:url(http://test.ing-site.com/facebook/findyourfriends_/includes/arrow-desc.png) no-repeat;padding-left:15px;" onclick="check_in_toggle(\'toggle\');">
			<span id="check-in_top_box_span">Check-in</span>
			</div>';
		echo '<div id="check-in_box" style="padding-top:5px;height:110px;">
			<div id="check-in_box_1" style="float:left;width:183px;">
			<span class="check-in_header">1. WHERE</span><br>Drag the marker to your location or right click on the map. We just took a wild guess :-)
			</div>
			<div id="check-in_box_2" style="float:left;width:183px;">
			<span class="check-in_header">2. WHAT</span><br><textarea rows="3" columns="50" onblur="change_textarea(\'onblur\');" onfocus="change_textarea(\'onfocus\');" style="color:#999999;width:174px;font-family:sans-serif;font-size:13px;" id="description_textarea">Describe what you\'re doing</textarea>
			</div>
			<div id="check-in_box_3" style="float:left;width:183px;">
			<span class="check-in_header">3. WHEN</span><br><input type="radio" id="check-in_when_now" checked="checked" name="check-in_when" value="0"> I\'m there now!<br><input type="radio" id="check-in_when_soon" name="check-in_when" value="1"> I\'ll be there in<br>
			<select id="check-in_when_hours" onchange="set_check_in_when();">
			<option value="0"> </option>
			<option value="1">1 hour</option>
			<option value="2">2 hours</option>
			<option value="3">3 hours</option>
			<option value="4">4 hours</option>
			<option value="5">5 hours</option>
			<option value="10">10 hours</option>
			<option value="24">1 day</option>
			</select> &<br><select id="check-in_when_minutes" onchange="set_check_in_when();">
			<option value="0"> </option>
			<option value="5">5 minutes</option>
			<option value="10">10 minutes</option>
			<option value="15">15 minutes</option>
			<option value="20">20 minutes</option>
			<option value="30">30 minutes</option>
			<option value="45">45 minutes</option>
			</select>
			</div>
			<div id="check-in_box_4" style="float:left;width:150px;">
			<span class="check-in_header">4. GO</span><br><input type="checkbox" id="post_to_wall" checked="checked" value="1"> Post to wall<br><br><button onclick="saveLocation();check_in_toggle(\'toggle\');">Check-in</button>
			</div>
			</div>';
		echo '</div>';

		// the map
		echo '<input type="hidden" id="lat" name="lat" value="">';
		echo '<input type="hidden" id="long" name="long" value="">';
		echo '<div id="map_canvas" style="height:300px;width:720px;"></div>';
		echo '<div id="friends_update" style="font-family:sans-serif;font-size:13px;">Hang tight while we look for your friends... <img src="includes/wait.gif"></div>';

		echo '</div>';

		// Google Analytics
		echo '<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(["_setAccount", "UA-1158303-3"]);
			_gaq.push(["_trackPageview"]);
			(function() {
				var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
				ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
				var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
			})();
			</script>';
	} catch (FacebookApiException $e) {
		echo "Error:" . print_r($e, true);
	}
}
