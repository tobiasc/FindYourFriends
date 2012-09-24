Titanium.include('sha.js');

Titanium.Facebook.appid = "160529684009816";
Titanium.Facebook.permissions = ['publish_stream', 'user_checkins', 'friends_checkins'];

var window = Ti.UI.createWindow();
var view = Ti.UI.createView({
	backgroundColor:"black"
});
window.add(view);

// capture
Titanium.Facebook.addEventListener('login', updateLoginStatus);
Titanium.Facebook.addEventListener('logout', updateLoginStatus);

var win1 = Titanium.UI.createWindow();
var tab1 = Titanium.UI.createTab({
    	icon:'KS_nav_phone.png',
	title:'Check-in',
    	window:win1
});

var win2 = Titanium.UI.createWindow();
var tab2 = Titanium.UI.createTab({
    	icon:'KS_nav_mashup.png',
	title:'Friend Map',
    	window:win2
});

var tabGroup = Titanium.UI.createTabGroup({id:'tabGroup1'});
tabGroup.addTab(tab1);
tabGroup.addTab(tab2);
tabGroup.addEventListener('open', function(){
	alert('open');	
	update_location();
	setInterval(function(){update_location();},5000);
});

Ti.Geolocation.preferredProvider = "gps";
var longitude = 0;
var latitude = 0;
var locationServicesEnabled_msg = false;
function update_location(){
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
				alert('update');
				if(mapview){
					var region = {'latitude':latitude, 'longitude':longitude, 'latitudeDelta':0.5, 'longitudeDelta':0.5};
					mapview.setLocation(region);
				}
			}
		});
	}
}

// Login Button
window.add ( Titanium.Facebook.createLoginButton({
	'style':'wide',
	top:100
}));

window.open();

var me;
var hash;
var friends;
var friends_length = 0;
function parse_me(e) {
	if (e.success) {
		me = JSON.parse(e.result);
		hash = sha1('him' + Titanium.Facebook.uid + 'self' + me.username);

		// update stored access token on the server
		var url1 = 'http://test.ing-site.com/facebook/findyourfriends_/save_access_token.php?b='+hash+'&a='+Titanium.Facebook.uid+'&access_token='+Titanium.Facebook.accessToken;
	    	var xhr1 = Titanium.Network.createHTTPClient();
		xhr1.onload = function(){
	
			// update friends locations
			var url2 = 'http://test.ing-site.com/facebook/findyourfriends_/find_checkins.php?b='+hash+'&a='+Titanium.Facebook.uid;
		    	var xhr1 = Titanium.Network.createHTTPClient();
		    	xhr1.open("GET", url2);
		    	xhr1.send();

			// get friends locations
			var url3 = 'http://test.ing-site.com/facebook/findyourfriends_/get_location.php?b='+hash+'&a='+Titanium.Facebook.uid+'&days=30';
		    	var xhr1 = Titanium.Network.createHTTPClient();
			xhr1.onload = function(){
				friends = JSON.parse(this.responseText);
				for(friend in friends){
					friends_length++;
				}
				actInd.hide();
				tabGroup.open();
				tabGroup.fireEvent('open');
		    	};
		    	xhr1.open("GET", url3);
		    	xhr1.send();
		};
	    	xhr1.open("GET", url1);
	    	xhr1.send();
		
	}
}

// the loading indicator
var actInd = Titanium.UI.createActivityIndicator({
	bottom:10, 
	height:50,
	width:10,
	style:Titanium.UI.iPhone.ActivityIndicatorStyle.PLAIN
});

function updateLoginStatus() {
	if(Titanium.Facebook.loggedIn){
		actInd.show();
		actInd.message = 'Hang tight while we look for your friends...';
		Titanium.Facebook.requestWithGraphPath('me', {}, 'GET', parse_me);		
	}
}

if(Titanium.Facebook.loggedIn){
	updateLoginStatus();
}

// friend map
var mapview = Titanium.Map.createView({
	mapType: Titanium.Map.STANDARD_TYPE,
	region:{'latitude':latitude, 'longitude':longitude, 'latitudeDelta':0.5, 'longitudeDelta':0.5},
	animate:true,
	regionFit:true
});
win2.add(mapview);

// check-in
var tf1 = Titanium.UI.createTextField({
	keyboardType: Titanium.UI.KEYBOARD_ASCII,
	returnKeyType: Titanium.UI.RETURNKEY_GO,
	top: 10,
	height: 40,
	hintText: 'Describe what you\'re doing'
});
win1.add(tf1);

var picker1 = Ti.UI.createPicker({
	top: 60,
	height: 40
});
var data1 = [];
data1[0] = Ti.UI.createPickerRow({title:'I\'m there now'});
data1[1] = Ti.UI.createPickerRow({title:'I\'m there soon'});
picker1.selectionIndicator = true;
picker1.add(data1);
picker1.setSelectedRow(0,0,true);
win1.add(picker1);

var picker2 = Ti.UI.createPicker({
	top: 110,
	height: 40
});
var data2 = [];
data2[0] = Ti.UI.createPickerRow({title:'Post to wall'});
data2[1] = Ti.UI.createPickerRow({title:'Don\'t post to wall'});
picker2.selectionIndicator = true;
picker2.add(data2);
picker2.setSelectedRow(0,0,true);
win1.add(picker2);

var b1 = Titanium.UI.createButton({
	top: 160,
	height: 40,
	title:'Check-in'
});
b1.addEventListener('click', function(){
	var description = (tf1.value)?tf1.value:'';
	var minutes = (picker1.getSelectedRow(0) == 'I\'m there now')?0:1;
	var hours = (picker1.getSelectedRow(0) == 'I\'m there now')?0:1;
	var post_to_wall = (picker2.getSelectedRow(0) == 'Post to wall')?1:0;
	alert('check-in at ('+latitude+', '+longitude+') with text: '+description+', hours: '+hours+', minutes: '+minutes+'post_to_wall: '+post_to_wall);
});
win1.add(b1);

