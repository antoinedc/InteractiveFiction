$(function() {
	
	{variables}
	var {key} = "{value}";
	{/variables}
	
	var sessionId = '';
	
	if ($('#charStats').html() == "")
		$('#charStats').hide();
	
	var isObjectEmpty = function(obj) {
	
		for (var el in obj)
			return false;
			
		return true;
	};
	
	if ($.cookie(sid))
	{
		sessionId = $.cookie(sid);
		$.ajax({
			url: BASE_URL + 'index.php/read/load/' + sid + '/' + sessionId,
			type: 'POST',
			dataType: 'json',
			beforeSend: function() {
			},
			success: function(data) {
				console.log(data);
				if (data.status > 0)
				{
					var notification = '<a href="#" id="continue">Continue the story</a>|<a href="#" id="restart">Start a new one</a>';
					$('#text').hide();
					$('#links').hide();
					$('#charStats').hide();
					$('#notifications').html(notification);
					
					$('a#continue').live('click', function() {
					
						$('#text').html(data.session.text);
						$('#links').empty();
						$.each(data.session.links, function(i, n) {
							var link = '<a href="#" id="' + n.destination + '">' + n.text + '</a><br />';
							$('#links').append(link);
						});
						$('#table').empty();
						
						for (var i in data.session.stats)
						{
							if (i != 'id' && i != 'main')
								$('#table').append('<tr><td>' + i + '</td><td>' + data.session.stats[i] + '</td></tr>');
						}
						
						$('#charStats').show();
						if (isObjectEmpty(data.session.stats) || $('#charStats').html() == "")
							$('#charStats').hide();
							
						$('#text').show();
						$('#links').show();
						$('#notifications').empty();
					});
					
					$('a#restart').live('click', function() {
					
						$('#text').show();
						
						$('#links').empty();
						$.each(data.session.links, function(i, n) {
							var link = '<a href="#" id="' + n.destination + '">' + n.text + '</a><br />';
							$('#links').append(link);
						});
						$('#links').show();
						
						if ($('#charStats').html() != "")
							$('#charStats').show();
							
						$.getJSON(BASE_URL + 'index.php/read/deleteSession/' + sessionId, function(data) {
							console.log(data);
						});
						$('#notifications').empty();
					});
				}
			},
			error: function(a, b, c) {
				console.log(a);
				console.log(b);
				console.log(c);
			}	
		});
	}
	else
		$.cookie(sid, sid + '-1-' + uniqId());

	$('#links > a').live('click', function() {

		$.ajax({
			url: BASE_URL + 'index.php/read/story/' + sid + '/' + $(this).attr('id') + '/' + $.cookie(sid),
			type: 'GET',
			dataType: 'json',
			beforeSend: function() {
			},
			success: function(data) {
				console.log(data);
				if (data.status > 0)
				{
					$('#text').html(data.text);
					$('#links').empty();
					$.each(data.links, function(i, n) {
						var link = '<a href="#" id="' + n.destination + '">' + n.text + '</a><br />';
						$('#links').append(link);
					});
					
					$('#table').empty();
					
					for (var i in data.stats.properties)
					{
						if (i != 'id' && i != 'main')
							$('#table').append('<tr><td>' + i + '</td><td>' + data.stats.properties[i] + '</td></tr>');
					}
					
					if (!data.stats.properties || $('#charStats').html() == "")
						$('#charStats').hide();
						
					if (data.isEnd == 'true')
					{
						var endText = '----------------------<br />\
									   End of the story<br />\
									   <a href="#" id="' + firstPid + '">Go back at the begginning</a>';
						
						$('#links').append(endText);
					}
				}
				else
					alert('An error has occured please retry');
			},
			error: function(a, b, c) {
				console.log(a);
				console.log(b);
				console.log(c);
				alert('An error has occured please retry');
			}				
		});
	});
	
	function uniqId() {		
		var S4 = function () {
			return Math.floor(Math.random() * 0x10000 /* 65536 */).toString(16);
		};
		return (S4() + S4() + "-" + S4() + "-" + S4() + "-" + S4() + "-" + S4() + S4() + S4());
	};
});