var my_location = find_location();

if(my_location){
	var win_friend_map = Titanium.UI.currentWindow;
	var mapview = Titanium.Map.createView({
		mapType: Titanium.Map.STANDARD_TYPE,
		region:{latitude:my_location.latitude, longitude:my_location.longitude, latitudeDelta:0.5, longitudeDelta:0.5},
		animate:true,
		regionFit:true
	});
	win_friend_map.add(mapview);
}
