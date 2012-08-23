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
		originId = $(this).parents('.paragraphToLink').attr('id');
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
	
});