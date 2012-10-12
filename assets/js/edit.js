$(function(){	
	
	if (!$('#onEditStory').length)
		return false;	
	
	var originId = '';
	var action;
	
	$('.addParagraph').live('click', function() {
		
		var sid = $(this).attr('id');
		var pid = $('#addParagraphModal').attr('data-pid');
		var text = CKEDITOR.instances['newParagraph'].getData();
		$.ajax({
		
			url: BASE_URL + 'index.php/edit/updateParagraph/' + sid,
			type: 'POST',
			dataType: 'json',
			data: {pid:pid,text:text, isFirst:($('#isFirstParagraph').attr('checked')=='checked'?'true':'false'), isEnd:($('#isEnd').attr('checked')=='checked'?'true':'false')},
			beforeSend: function() {
				
			},
			success: function(data) {
				if (data.status > 0)
					$('.w#'+pid+'>.tooltip').html(text);
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
	
	$('#addLinkModal').on('show', function() {
	
		var sid = $('#addLinkModal').attr('data-sid');
		var pid = $('#addLinkModal').attr('data-pid');
		var lid = $('#addLinkModal').attr('data-lid');
		
		if (lid)
		{
			$.getJSON(BASE_URL + 'index.php/edit/getLink/' + sid + '/' + pid + '/' + lid, function(data) {
				console.log(data);
				if (data.status > 0)
				{
					$('input#choice').attr('value', data.link.text);
					$('#isFirstParagraph').attr('checked', false);
					$('#isEnd').attr('checked', false);
				}
			});
		}
	});

	$('#addLinkModal').find('.modal-footer').find('.save').live('click', function() {
		
		if (document.getElementById('choice').value == '')
		{
			alert('You have to put a text');
			return;
		}
		
		var sid = $('#addLinkModal').attr('data-sid');
		var originId = $('#addLinkModal').find('.linkSource').attr('id');
		var destId = $('#addLinkModal').find('.linkTarget').attr('id');
		var lid = $('#addLinkModal').attr('data-lid');
		var operation = $('.selectOperation').attr('value');
		var newValue = $('.newValue').val();
		var prop = $(this).children('.key').text();
	
		action = {
			key:prop,
			operation:operation,
			value:newValue			
		};
		
		$.ajax({
		
			url: BASE_URL + 'index.php/edit/addLink',
			type: 'POST',
			dataType: 'json',
			data: {lid:lid, originid:originId,destid:destId, sid:sid, text:$(this).parents('#addLinkModal').find('.modal-body').find(':input#choice').val(),action:action},
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
				$('#addLinkModal').hide();
				
				console.log(data);
				
				if (data.status > 0)
				{
					if ($('#addLinkModal').attr('data-conn-id'))
					{
						jsPlumb.select().each(function(connection) {
							
							if (connection.id == $('#addLinkModal').attr('data-conn-id'))
							{
								connection.setParameters({lid:data.lid});
								return;
							}
						});
					}
				}
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
				if (data.status == 1)
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
		
		if ($(this).parent().parent().children('.input-prepend').length-1)
			$(this).parent().remove();
	});
	
	$('.updateMainCharStats').live('click', function() {
		
		var properties = {};
		$('.mainCharStat > .input-prepend').each(function(i) {
			properties[i] = {key:$(this).children('.key').val(), value:$(this).children('.value').val()}
		});
		
		$.ajax({
		
			url: BASE_URL + 'index.php/edit/addCharProperties/0',
			type: 'POST',
			data: {properties:properties, sid:$(this).attr('id')},
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
	
	$('#addNewChar').live('click', function() {
		
		$('.editOtherChar').empty();
		var mandProp = '<div class="otherCharProp input-prepend mandatory">\
							<h5>Mandatory properties</h5>\
							<input type="hidden" class="key" value="name" />\
							Name of the new character: <input type="text" class="otherCharName value span4" />\
						</div>';
							
		var optProp =  '<div class="input-prepend otherCharProp">\
							<h5>Optional properties</h5>\
							Name: <input type="text" class="key span4" />\
							Value: <input type="text" class="value span4" />\
							<a class="btn" href="#" id="addStat"><i class="icon-plus"></i></a>\
							<a class="btn" href="#" id="rmStat"><i class="icon-minus"></i></a>\
						</div>';
						
		$('.editOtherChar').html('<hr />' + mandProp + '<hr />' + optProp);
	});
	
	$('.validateAddNewChars').live('click', function() {
	
		var properties = [];
		$('.editOtherChar > .input-prepend').each(function(i) {
			properties[i] = {key:$(this).children('.key').val(), value:$(this).children('.value').val()}
		});
		
		cid = $('.otherCharSelector').attr('value');
			
		$.ajax({
		
			url: BASE_URL + 'index.php/edit/addCharProperties/' + cid,
			type: 'POST',
			data: {properties:properties, sid:$(this).attr('id')},
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
	
	$('.otherCharSelector').change(function() {
	
		if ($(this).attr('value') == -1) return;
		
		var cid = $(this).attr('value');
		
		$.ajax({
		
			url: BASE_URL + 'index.php/edit/getCharProperties/' + cid,
			type: 'POST',
			data: {sid:$('.validateAddNewChars').attr('id')},
			dataType: 'json',
			beforeSend: function() {
			},
			success: function(data) {
				$('.editOtherChar').empty();
				$('.editOtherChar').append('<hr/>');
				$('.editOtherChar').append('<h5>Properties</h5>');
				$.each(data, function(i, el) {
					if (i != '_id' && i != 'status')
					{
						var html = '<div class="input-prepend">\
										Name: <input type="text" value="' + i + '" class="key span4" />\
										Value: <input type="text" value="' + el + '" class="value span4" />\
										<a class="btn" href="#" id="addStat"><i class="icon-plus"></i></a>\
										<a class="btn" href="#" id="rmStat"><i class="icon-minus"></i></a>\
									</div>';
						$('.editOtherChar').append(html);
					}
				});
			},
			error: function(a, b, c) {
				console.log(a);
				console.log(b);
				console.log(c);
			}
		});
	});
});