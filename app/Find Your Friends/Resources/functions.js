function find_location(){
	Ti.Geolocation.preferredProvider = "gps";
	var longitude = 0;
	var latitude = 0;

	if (Titanium.Geolocation.locationServicesEnabled == false){
		Titanium.UI.createAlertDialog({title:'Find Your Friends', message:'Your device has the GPS turned off, Find Your Friends needs a GPS to work'}).show();
	} else {
		Titanium.Geolocation.accuracy = Titanium.Geolocation.ACCURACY_BEST;
		Titanium.Geolocation.getCurrentPosition(function(e){
			if (e.success){
				longitude = e.coords.longitude;
				latitude = e.coords.latitude;
			}
		});
	}
	return {'latitude':latitude, 'longitude': longitude};
}
