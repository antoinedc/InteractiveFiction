$(function(){	
	
	if (!$('#onEditStory').length)
		return false;	
	
	var originId = '';
	var action;

	$('.addLinkModal').live('click', function() {
		originId = $(this).parents('.paragraph').attr('id');
	});

	$('#editOthersCharacterModal').on('show', function() {
	
		$('.editOtherChar').empty();
		$('.otherCharSelector > option[value=-1]').attr('selected', true);
	});
	
	$('.generateHtml').live('click', function() {
		
		$.ajax({
		
			url: BASE_URL + 'index.php/generate/html/' + $(this).attr('id'),
			dataType: 'json',
			beforeSend: function() {
				$.blockUI({
					message: 'Generating story..',
					css: 
					{ 
						border: 'none', 
						padding: '15px', 
						backgroundColor: '#000', 
						'-webkit-border-radius': '10px', 
						'-moz-border-radius': '10px', 
						opacity: .5, 
						color: '#fff' 
					}
				});
			},
			success: function(data) {
			
				$.unblockUI();	
				if (data.status == true)
				{
					$.growlUI($('div.growlUI.success').html());
					console.log(data);
					$('#hereIsYourLink').html('<a href="http://' + data.url + '" target="_blank">http://' + data.url + '</a>');
					$('#publishOnlineModal').modal('show');
				}
				else if (data.status == -2)
					alert('You need to create one paragraph at least.');
				else if (data.status == -3)
					alert('An error occured while contacting the remote service, it maybe caused by a proxy.');
				else
					alert('Unexpected error, please retry.');
			},
			error: function(a, b, c) {
				$.unblockUI();
				console.log(a);
				console.log(b);
				console.log(c);
			}	
		});
	});
	
	$('.generateMxit').live('click', function() {
	
		$.ajax({
		
			url: BASE_URL + 'index.php/generate/mxit/' + $(this).attr('id'),
			dataType: 'json',
			beforeSend: function() {
				$.blockUI({
					message: 'Generating story..',
					css: 
					{ 
						border: 'none', 
						padding: '15px', 
						backgroundColor: '#000', 
						'-webkit-border-radius': '10px', 
						'-moz-border-radius': '10px', 
						opacity: .5, 
						color: '#fff' 
					}
				});
			},
			success: function(data) {
				
				$.unblockUI();
				if (data.status == 1)
					$.growlUI($('div.growlUI.successMxit').html());
				else if (data.status == -2)
					alert('You need to create one paragraph at least.');
				
			},
			error: function(a, b, c) {
				$.unblockUI();
				console.log(a);
				console.log(b);
				console.log(c);
			}
		});
	});
	
	$('#addStat').live('click', function() {
		
		var id = $(this).parent().children('.input-prepend').length;
		
		var html = '<div class="input-prepend">\
						Name: <input type="text" class="key span4" />\
						Value: <input type="text" class="value span4" />\
						<a class="btn" href="#" id="addStat"><i class="icon-plus"></i></a>\
						<a class="btn" href="#" id="rmStat"><i class="icon-minus"></i></a>\
					</div>';
					
		$(this).parent().parent().append(html);
	});
	
	$('#rmStat').live('click', function() {
		
		if ($(this).parent().parent().children('.input-prepend').length-1 && $(this).siblings('.key:input').val() != 'name')
			$(this).parent().remove();
			
	});
});