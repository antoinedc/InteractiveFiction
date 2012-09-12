$(function() {
	
	{variables}
		var {key} = "{value}";
	{/variables}

	$('a').click(function() {
		$.ajax({
			url: BASE_URL + 'index.php/read/story' + sid + '/' + $(this).attr('id'),
			type: 'GET',
			dataType: 'json',
			beforeSend: function() {
			},
			success: function(data) {
				console.log(data);
			},
			error: function(a, b, c) {
				console.log(a);
				console.log(b);
				console.log(c);
			}				
		});
	});
});