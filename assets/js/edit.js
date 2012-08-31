$(function(){	
	
	if (!$('#onEditStory').length)
		return false;
	
	var originId = '';
	
	$('.addParagraph').live('click', function() {
		
		var sid = $(this).attr('id');
		
		$.ajax({
		
			url: BASE_URL + 'index.php/edit/addParagraph',
			type: 'POST',
			dataType: 'json',
			data: {sid:sid, content:$('#newParagraph').val(), isFirst:($('#isFirstParagraph').attr('checked')=='checked'?'true':'false'), isEnd:($('#isEnd').attr('checked')=='checked'?'true':'false')},
			beforeSend: function() {
				$('.paragraph').last().after('<div class="paragraph span8 well">Loading...</div>');
			},
			success: function(data) {
				if (data.status < 0)
					$('.paragraph').last().remove();
				$('.paragraph').last().html($('#newParagraph').val().replace(/(\\n|\n)/g,"<br />" )+'<a href="#addLinkModal" class="btn addLinkModal" style="float:right;" data-toggle="modal">Add link</a>');
			},
			error:function(a, b, c){
				console.log(a);
				console.log(b);
				console.log(c);
			}		
		});
	});
	
	$('.addLinkModal').live('click', function() {
		originId = $(this).parents('.paragraph').attr('id');
	});
	
	$('.chooseDest').hover(function(){
		$(this).css({'border-color':'red'});
		$(this).css('cursor','pointer');
	}, function() {
		$(this).css({'border-color':''});
		$(this).css('cursor','auto');
	}).click(function(){
		
		$.ajax({
			
			url: BASE_URL + 'index.php/edit/addLink',
			type: 'POST',
			dataType: 'json',
			data: {originid:originId,destid:$(this).attr('id'), sid:$(this).parents('.modal-body').siblings('.modal-footer').children().last().attr('id'), text:$(this).parents('#addLinkModal').children('.modal-body').children(':input#choice').val()},
			beforeSend: function() {
				$('#addLinkModal').block({
					message: 'Creating link...',
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
			success: function(data){
				$('#addLinkModal').unblock();
				console.log(data);
			},
			error: function(a, b, c) {
				$('#addLinkModal').unblock();
				console.log(a);
				console.log(b);
				console.log(c);
			}		
		});
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
				$.growlUI($('div.growlUI.success').html());
				console.log(data);
				$('.paragraph').first().before('<div class="paragraph span8 well">Link to your story: <a target="_blank" href="http://' + data.url + '">' + data.url + '</a></div>');
				
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
				$.growlUI($('div.growlUI.successMxit').html());
				console.log(data);
			},
			error: function(a, b, c) {
				$.unblockUI();
				console.log(a);
				console.log(b);
				console.log(c);
			}
		});
	});
	
});