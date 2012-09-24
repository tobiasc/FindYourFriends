// check-in
var win_checkin2 = Titanium.UI.currentWindow;

var tf1 = Titanium.UI.createTextField({
	keyboardType: Titanium.UI.KEYBOARD_ASCII,
	returnKeyType: Titanium.UI.RETURNKEY_GO,
	top: 10,
	height: 40,
	hintText: 'Describe what you\'re doing'
});
win_checkin2.add(tf1);

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
win_checkin2.add(picker1);

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
win_checkin2.add(picker2);

var actInd = Titanium.UI.createActivityIndicator({
	bottom:10, 
	height:50,
	width:10,
	style:Titanium.UI.iPhone.ActivityIndicatorStyle.PLAIN
});

var b1 = Titanium.UI.createButton({
	top: 160,
	height: 40,
	title:'Check-in'
});
b1.addEventListener('click', function(){
	actInd.show();
	actInd.message = 'Checking in...';

	var description = (tf1.value)?tf1.value:'';
	var minutes = (picker1.getSelectedRow(0) == 'I\'m there now')?0:1;
	var hours = (picker1.getSelectedRow(0) == 'I\'m there now')?0:1;
	var post_to_wall = (picker2.getSelectedRow(0) == 'Post to wall')?1:0;

	if(Titanium.UI.currentWindow.latitude == undefined){
		// find users location
		if (Titanium.Geolocation.locationServicesEnabled){
			Titanium.Geolocation.accuracy = Titanium.Geolocation.ACCURACY_BEST;
			Titanium.Geolocation.getCurrentPosition(function(e){
				if (e.success){
					Titanium.UI.currentWindow.longitude = e.coords.longitude;
					Titanium.UI.currentWindow.latitude = e.coords.latitude;
					var url1 = 'http://test.ing-site.com/facebook/findyourfriends_/save_location.php?post_to_wall='+post_to_wall+'&description='+description+'&hours='+hours+'&minutes='+minutes+'&lat='+Titanium.UI.currentWindow.latitude+'&long='+Titanium.UI.currentWindow.longitude+'&b='+Titanium.UI.currentWindow.hash+'&a='+Titanium.Facebook.uid;
					alert(url1);
				    	var xhr1 = Titanium.Network.createHTTPClient();
					xhr1.onload = function(){
						actInd.hide();
						//Titanium.UI.currentWindow.tabGroup.setActiveTab(2);
					};
				    	xhr1.open("GET", url1);
				    	xhr1.send();	
				}
			});
		}
	} else {
		var url1 = 'http://test.ing-site.com/facebook/findyourfriends_/save_location.php?post_to_wall='+post_to_wall+'&description='+description+'&hours='+hours+'&minutes='+minutes+'&lat='+Titanium.UI.currentWindow.latitude+'&long='+Titanium.UI.currentWindow.longitude+'&b='+Titanium.UI.currentWindow.hash+'&a='+Titanium.Facebook.uid;
		alert(url1);
	    	var xhr1 = Titanium.Network.createHTTPClient();
		xhr1.onload = function(){
			actInd.hide();
			//Titanium.UI.currentWindow.tabGroup.setActiveTab(2);
		};
	    	xhr1.open("GET", url1);
	    	xhr1.send();	
	}
});
win_checkin2.add(b1);
