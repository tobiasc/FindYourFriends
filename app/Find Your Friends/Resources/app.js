Titanium.include('sha.js');

var win_login = Titanium.UI.createWindow();
var tab_login = Titanium.UI.createTab({
    	icon:'KS_nav_phone.png',
	title:'Log in',
    	window:win_login
});

var win_checkin = Titanium.UI.createWindow({
	url:'checkin.js',
});
var tab_checkin = Titanium.UI.createTab({
    	icon:'KS_nav_phone.png',
	title:'Check-in',	
    	window:win_checkin
});
win_checkin.hash = 0;

// maps doesn't show if they're the first tab!
var win_map = Titanium.UI.createWindow();
var tab_map = Titanium.UI.createTab({
    	icon:'KS_nav_mashup.png',
	title:'Friend Map',
    	window:win_map
});

var tabGroup = Titanium.UI.createTabGroup({id:'tabGroup1'});
tabGroup.addTab(tab_login);
win_checkin.tabGroup = tabGroup;

Ti.Geolocation.preferredProvider = "gps";
var longitude = 0;
var latitude = 0;
var friends;
var friends_length = 0;
var locationServicesEnabled_msg = false;
var location_centered = false;
var location_centered_friends = false;
var nearest_friend = 1000;
var friend_annotations = new Array();
var user_annotation = false;
var days = 30;
var indicator = false;
var first_run = true;
function update_location(){
	if(Titanium.Facebook.loggedIn){
		win_checkin.hash = hash;

		// show activity indicator first time
		if(!user_annotation){
			actInd.show();
			actInd.message = 'Hang tight while we look for your friends...';
			indicator = true;
		}

		// find users location
		if (Titanium.Geolocation.locationServicesEnabled == false){
			if(!locationServicesEnabled_msg){
				locationServicesEnabled_msg = true;
				Titanium.UI.createAlertDialog({title:'Find Your Friends', message:'Your device has the GPS turned off, Find Your Friends needs a GPS to work'}).show();
			}
		} else {
			Titanium.Geolocation.accuracy = Titanium.Geolocation.ACCURACY_BEST;
			Titanium.Geolocation.getCurrentPosition(function(e){
				if (e.success){
					longitude = e.coords.longitude;
					latitude = e.coords.latitude;
					win_checkin.longitude = longitude;
					win_checkin.latitude = latitude;

					// center map on GPS location first time
					if(mapview && !location_centered){
						location_centered = true;
						var region = {'latitude':latitude, 'longitude':longitude, 'latitudeDelta':0.1, 'longitudeDelta':0.1};
						mapview.setLocation(region);
					}

					// add user annotation (remove old one if present)
					if(user_annotation){
						mapview.removeAnnotation(user_annotation);
					}
					user_annotation = Titanium.Map.createAnnotation({
						latitude:latitude,
						longitude:longitude,
						title:me.name,
						pincolor: Titanium.Map.ANNOTATION_GREEN,
						animate:true
					});
					mapview.addAnnotation(user_annotation);
				}
			});
		}

		// get friends locations
		var url3 = 'http://test.ing-site.com/facebook/findyourfriends_/get_location.php?b='+hash+'&a='+Titanium.Facebook.uid+'&days=30';
	    	var xhr1 = Titanium.Network.createHTTPClient();
		xhr1.onload = function(){
			friends = JSON.parse(this.responseText);
			for(friend in friends){
				if(!location_centered_friends){
					var distance = Math.sqrt(Math.pow(friends[friend].lat - latitude, 2) + Math.pow(friends[friend].lng - longitude, 2));
					nearest_friend = (distance < nearest_friend)?distance:nearest_friend;
				}
				friends_length++;
				if(friends[friend].days <= days && !(friend in friend_annotations)){
					friend_annotations[friend] = Titanium.Map.createAnnotation({
						latitude:friends[friend].lat,
						longitude:friends[friend].lng,
						title:friends[friend].name,
						subtitle:friends[friend].description + "\n" + friends[friend].time_text,
						pincolor: Titanium.Map.ANNOTATION_RED,
						animate:true
					});
					mapview.addAnnotation(friend_annotations[friend]);
				} else if(friends[friend].days > days && friend in friend_annotations){
					mapview.removeAnnotation(friend_annotations[friend]);
				}
			}
			if(first_run && friends_length > 0){
				first_run = false;
				tabGroup.setActiveTab(2);
			}
			if(!location_centered_friends){
				location_centered_friends = true;
				var region = {'latitude':latitude, 'longitude':longitude, 'latitudeDelta':nearest_friend*2, 'longitudeDelta':nearest_friend*2};
				mapview.setLocation(region);
				if(indicator){
					indicator = false;
					actInd.hide();
				}
			}
	    	};
	    	xhr1.open("GET", url3);
	    	xhr1.send();
	}
}

var me;
var hash;
function parse_me(e) {
	if (e.success) {
		me = JSON.parse(e.result);
		hash = sha1('him' + Titanium.Facebook.uid + 'self' + me.username);
		tabGroup.addTab(tab_checkin);
		tabGroup.addTab(tab_map);

		// update stored access token on the server
		var url1 = 'http://test.ing-site.com/facebook/findyourfriends_/save_access_token.php?b='+hash+'&a='+Titanium.Facebook.uid+'&access_token='+Titanium.Facebook.accessToken;
	    	var xhr1 = Titanium.Network.createHTTPClient();
		xhr1.onload = function(){
			// update friends locations
			var url2 = 'http://test.ing-site.com/facebook/findyourfriends_/find_checkins.php?b='+hash+'&a='+Titanium.Facebook.uid;
		    	var xhr1 = Titanium.Network.createHTTPClient();
		    	xhr1.open("GET", url2);
		    	xhr1.send();

			update_location();
		};
	    	xhr1.open("GET", url1);
	    	xhr1.send();
	}
}

// friend map
var updates_enabled = false;
var mapview = Titanium.Map.createView({
	mapType: Titanium.Map.STANDARD_TYPE,
	region:{'latitude':latitude, 'longitude':longitude, 'latitudeDelta':0.5, 'longitudeDelta':0.5},
	animate:true,
	regionFit:true
});
win_map.add(mapview);
var update_interval = false;
mapview.addEventListener('regionChanged', function(){
	if(Titanium.Facebook.loggedIn && !updates_enabled){
		updates_enabled = true;
		update_location();
		update_interval = setInterval(function(){update_location();},10000);
	}
});

// log in/log out
function updateLoginStatus() {
	if(Titanium.Facebook.loggedIn){
		Titanium.Facebook.requestWithGraphPath('me', {}, 'GET', parse_me);
	} else {
		
	}
}

Titanium.Facebook.appid = "160529684009816";
Titanium.Facebook.permissions = ['publish_stream', 'user_checkins', 'friends_checkins'];
Titanium.Facebook.addEventListener('login', updateLoginStatus);
Titanium.Facebook.addEventListener('logout', updateLoginStatus);

var actInd = Titanium.UI.createActivityIndicator({
	bottom:10, 
	height:50,
	width:10,
	style:Titanium.UI.iPhone.ActivityIndicatorStyle.PLAIN
});
updateLoginStatus();

win_login.add ( Titanium.Facebook.createLoginButton({
	'style':'wide',
	top:100
}));

// the tabGroup.open function breaks all eventListeners, callbacks, & timeOut/interval's set!
tabGroup.open();

