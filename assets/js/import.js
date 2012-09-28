$(function() {

	if ($('#importastory').length != 1)
		return;
		
	$('#btn-import').on('click', function() {
	
		$.ajax({
		
			url: BASE_URL + 'index.php/import/lonewolf',
			type: 'POST',
			dataType: 'json',
			data: {url: $('#svg-url').val()},
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