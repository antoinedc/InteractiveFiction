$(function() {

	if (!$('#onHome').length) return false;	
	
	$.getJSON(BASE_URL + 'index.php/home/getUserInfos', function(data) {
		
		console.log('getUserInfos: ', data);
		if (data.status > 0)
		{
			mixpanel.people.identify(data.id);
			mixpanel.people.set({
				"$email": data.email,
				"$last_login": new Date()
			});
		}
	});
});