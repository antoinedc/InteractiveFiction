$(function() {
	
	if (!$('#onEditStory').length) return false;	

	var curColourIndex = 1, maxColourIndex = 24, nextColour = function() {
		var R,G,B;
		R = parseInt(128+Math.sin((curColourIndex*3+0)*1.3)*128);
		G = parseInt(128+Math.sin((curColourIndex*3+1)*1.3)*128);
		B = parseInt(128+Math.sin((curColourIndex*3+2)*1.3)*128);
		curColourIndex = curColourIndex + 1;
		if (curColourIndex > maxColourIndex) curColourIndex = 1;
		return "rgb(" + R + "," + G + "," + B + ")";
	};
	
	var selected;
	var Story = function(id) {
	
		this.id = id;
		this.owner = '';
		this.title = '';
		this.start = 0;
		this.paragraphes = [];
		this.characters = [];
		
		this.update = function(callback) {
		
			this.owner = 'antoine';
			console.log(JSON.stringify($(this)));
			$.post(BASE_URL + 'index.php/edit/update', {story: JSON.stringify($(this))}, function(data) {
			
				if (data.status <= 0)
				{
					if (callback)
						callback(false);
					return;
				}
				if (callback)
					callback(true);
				
			}, 'json');
		};
		
		this.setStart = function(pid) {
		
			var p = this.getParagraph(pid);
			p.isStart = true;
			this.start = pid;
			
			for (var i = 0; i < this.paragraphes.length; i++)
				if (this.paragraphes[i].id != pid)
					this.paragraphes[i].isStart = false;
					
		};
		
		this.getParagraph = function(pid) {
		
			var res = false;
			for (var i = 0; i < this.paragraphes.length; i++)
				if (this.paragraphes[i].id == pid)
					res = this.paragraphes[i];
			
			return false;
		};
		
		this.removeParagraph = function(pid) {
		
			var temp = [];
			
			for (var i = 0; i < this.paragraphes.length; i++)
				if (this.paragraphes[i].id != pid)
					temp.push(this.paragraphes[i]);
			
			this.paragraphes = temp;
			
			for (var i = 0; i < this.paragraphes.length; i++)
				for (var j = 0; j < this.paragraphes[i].links.length; j++)
					if (this.paragraphes[i].links[j].destination == pid)
						this.paragraphes[i].removeLink(this.paragraphes[i].links[j].id);
		};
		
		this.removeCharacter = function(cid) {
		
			for (var i = 0; i < this.characters.length; i++)
				if (this.characters[i].id == cid)
				{
					this.characters.slice(i, i+1);
					break;
				}
		};
		
		this.addParagraph = function(isStart, isEnd, text, links, callback) {
		
			var newParagraph = new Paragraph(0);
			newParagraph.sid = this.id;
			newParagraph.isEnd = isEnd;
			newParagraph.isStart = isStart;
			newParagraph.text = text;
			newParagraph.links = links;
			
			var data = {
			
				sid: this.id,
				content: text,
				isFirst: isStart,
				isEnd: isEnd
			};
			
			$.post(BASE_URL + 'index.php/edit/addParagraph', data, function(data) {
			
				if (data.status > 0)
				{
					newParagraph.id = data.id.$id;
					if (callback) 
						callback(true, newParagraph);
				}
				else
					if (callback)
						callback(false);
			}, 'json');
		};
		
		this.addCharacter = function(main, properties) {
		
			var newCharacter = new Character(0);
			newCharacter.main = main;
			newCharacter.properties = properties,
			
			this.characters.push(newCharacter);
			return newCharacter;
		};
		
		this.getParagraph = function(id) {
		
			var res = false;
			for (var i = 0; i < this.paragraphes.length; i++)
			{
				if (this.paragraphes[i].id == id)
				{
					res = this.paragraphes[i];
					break;
				}
			}
			return res;
		};
		
		this.getCharacter = function(id) {
		
			var res = false;
			for (var i = 0; i < this.characters.length; i++)
				if (this.characters[i].id == id)
				{
					res = this.characters[i];
					break;
				}
			return res;
		};
		
		this.getCharByName = function(name) {
		
			var res = false
			
			for (var i = 1; i < this.characters.length; i++)
				if (this.characters[i].properties['name'] == name)
					return this.characters[i];
			
			return res;
		};
		
		this.getMainCharacter = function() {
		
			var res = false;
			
			for (var i = 0; i < this.characters.length; i++)
				if (this.characters[i].properties.main == "true")
					res = this.characters[i];
			return res;			
		};
	};
	
	var Paragraph = function(id) {
	
		this.id = id;
		this.sid = 0;
		this.isEnd = false;
		this.isStart = false;
		this.text = '';
		this.x = 50;
		this.y = 50;
		this.links = [];
		
		this.addLink = function(source, target, text, action, callback) {
		
			var newLink = new Link(0);
			newLink.sid = this.sid;
			newLink.origin = source;
			newLink.destination = target;
			newLink.text = text;
			newLink.action = action;
			
			var data = {
			
				originid: source,
				destid: target,
				sid: this.sid,
				text: text,
				action: action
			};
			$.post(BASE_URL + 'index.php/edit/addLink/', data, function(data) {
			
				if (data.status > 0)
				{
					newLink.id = data.lid;
					if (callback)
						callback(true, newLink);
				}
				else
					callback(false);
			}, 'json');
		};
		
		this.getLink = function(id) {
		
			var res = false;
			
			for (var i = 0; i < this.links.length; i++)
				if (this.links[i].id == id)
				{
					res = this.links[i];
					break;
				}
				
			return res;
		};
				
		this.removeLink = function(lid) {
			
			for (var j = 0; j < this.links.length; j++)
				if (this.links[j].id == lid)
					this.links = this.links.slice(j, j+1);
			
			return true;
		};
	};
	
	var Link = function(id) {
	
		this.id = id;
		this.sid = 0;
		this.origin = 0;
		this.destination = 0;
		this.text = '';
		this.action = [];
	};
	
	var Action = function() {
		
		this.key = '';
		this.operation = -1;
		this.value = '';
	};
	
	var Character = function(id) {
	
		this.id = id;
		this.main = false;
		this.properties = {} /**A property is a json object: {key:'age', value:'22'} no need to define another class for this**/
	};
	
	var initialize = function(callback) {
		
		var story = new Story($('#onEditStory').attr('data-story-id'));
		
		$.getJSON(BASE_URL + 'index.php/edit/getStory/' + story.id, function(data) {
			
			console.log(data);
			
			if (data.status <= 0)
			{
				alert('Something went wrong, please reload the page.');
				return;
			}
			
			var s = data.story;
			
			story.owner = s.owner;
			story.title = s.title;
			story.start = s.start;
			
			if (s.paragraphes)
				s.paragraphes.forEach(function(el, i) {
					
					var newParagraph = new Paragraph(el._id.$id);
					newParagraph.sid = story.id;
					newParagraph.isEnd = el.isEnd;
					newParagraph.isStart = el.isStart;
					newParagraph.text = el.text;
					newParagraph.x = el.x;
					newParagraph.y = el.y;
					
					if (el.links)
						el.links.forEach(function(link, i) {
						
							var newLink = new Link(link._id.$id);
							newLink.sid = link.sid;
							newLink.origin = link.origin;
							newLink.destination = link.destination;
							newLink.text = link.text;
							
							if (link.actions)
								link.actions.forEach(function(action, i) {
								
									var newAction = new Action;
									newAction.key = action.key;
									newAction.operation = action.operation;
									newAction.value = action.value;
									
									newLink.actions.push(newAction);
								});
							
							newParagraph.links.push(newLink);
						});
					
					story.paragraphes.push(newParagraph);
				});
			
			if (s.characters)
				s.characters.forEach(function(el, i) {
				
					var newCharacter = new Character(i);
					for (var property in el)
						if (property == '_id')
							newCharacter.id = el._id.$id;
						else
							newCharacter.properties[property] = el[property];
					
					story.characters.push(newCharacter);
				});
				
			console.log(story);
			
			callback(story);
		});
	};

	var getExcerpt = function (text) {
	
		var result = '';
		var split = text.split(' ');
		for (var i = 0; i < 10; i++)
			result += split[i] + ' ';
		return result.slice(result.length - 1);
	};
	
	jsPlumb.ready(function() {
		
		initialize(function(story) {
		
			var sid = story.id;
			/*var oldStory = story;

			$(window).bind('beforeunload', function() {
				
				if (!Object.identical(story, oldStory)) {
					return 'Des changements n\'ont pas été sauvegardés si vous quittez maintenant, vous les perdrez !';
				}
			});*/
			
			var canvas = document.getElementById('canvas');
			var translatePos = {
				x: canvas.width / 2,
				y: canvas.height / 2
			};
			
			var nodes = {};
			var links = [];
			
			/**Canvas init**/
			$('#canvas').css('cursor', 'url(https://mail.google.com/mail/images/2/openhand.cur), default');
		
			$('#canvas').mousedown(function() {
				$('#canvas').css('cursor', 'url(https://mail.google.com/mail/images/2/closedhand.cur), default');
			}).mouseup(function() {
				$('#canvas').css('cursor', 'url(https://mail.google.com/mail/images/2/openhand.cur), default');
			});
			
			$(canvas).draggable({
			
				drag: function(event, ui) {
					
					$( 'canvas' ).each(function(i, el) {
					
						el.getContext("2d").translate(translatePos.x, translatePos.y);
					});
				}
			});
			/***************/
			
			/***jsPlumb init***/
			jsPlumbDefaultsSettings = {
				Endpoint : ["Dot", {radius:2}],
				HoverPaintStyle : {strokeStyle:"#42a62c", lineWidth:2 },
				ConnectionOverlays : [
					[ "Arrow", { 
						location:1,
						id:"arrow",
						length:14,
						foldback:0.8
					} ]
				]
			};
			
			jsPlumb.importDefaults(jsPlumbDefaultsSettings);
		//	jsPlumb.setRenderMode(jsPlumb.CANVAS);
			jsPlumb.draggable(jsPlumb.getSelector(".w"));
			
			$('.w').each(function(i, e) {
				
				$(this).css({
					left: story.getParagraph($(this).attr('id')).x,
					top: story.getParagraph($(this).attr('id')).y
				});
			});
			
			$('.ep').each(function(i, e) {
				
				p = $(e).parent();
				
				jsPlumb.makeSource($(e), {
					
					parent: p,
					anchor: "Continuous",
					connector: [ "StateMachine", { curviness:20 } ],
					connectorStyle:{ strokeStyle:nextColour(), lineWidth:2 }
				});
			});
			
			jsPlumb.makeTarget(jsPlumb.getSelector(".w"), {
				
				dropOptions:{ hoverClass:"dragHover" },
				anchor:"Continuous"	,
				beforeDrop: function(conn) {
					
					var modal = $('#addLinkModal');
					$('#choice').attr('value', '');
					$('#newValue').attr('value', '');
					modal.attr('data-lid', '');					
					modal.attr('data-conn-id', conn.connection.id);					
					modal.attr('data-sourceid', conn.sourceId);
					modal.attr('data-targetid', conn.targetId);
					
					$('#addLinkModal').modal('show');
					
					return false;
				}			
			});
			
			jsPlumb.bind("jsPlumbConnection", function(conn) {
					
				conn.connection.setPaintStyle({strokeStyle:nextColour()});
				conn.connection.bind('click', function(conn) {
					
					/**TODO: improve the way we pass these ids**/
					var modal = $('#addLinkModal');
					modal.attr('data-lid', conn.getParameter('lid'));
					modal.attr('data-pid', conn.sourceId);
					modal.attr('data-sourceid', conn.sourceId);
					modal.attr('data-targetid', conn.targetId);
					modal.modal('show');
				});
			});
			
			$('.w').on('click', function() {
				
				$('.w').css({
					'border':'1px solid #346789'
				});
				
				$(this).css({
					'border': 'red 1px solid'
				});
				
				selected = $(this).attr('id');
				
				$(document).keyup(function(e) {
				
					if (e.keyCode == 46)
					{
						if (!selected)
							return;
							
						var pid = selected;
						story.removeParagraph(pid);
						story.update(function(status) {
						
							if (status)
							{
								jsPlumb.detachAllConnections(pid);
								$('.w#' + pid).remove();
							}
						});
					}
				});
			});
			
			$('.w').on('dblclick', function(e) {
				
				CKEDITOR.instances['newParagraph'].setData($(this).children('.tooltip').text());
				$('#addParagraphModal').css('visibility','visible');
				
				$('#addParagraphModal').attr('data-pid', e.currentTarget.id);
				var paragraph = story.getParagraph(e.currentTarget.id);
				
				if (story.paragraphes.length > 1 && $('.w').length > 1)
					$('#isFirstParagraph').removeAttr('disabled');
				console.log(e.currentTarget.id, story.start);
				$('#isEnd').attr('checked', false);
				$('#isFirstParagraph').attr({
				
					'checked': story.start == e.currentTarget.id
				});
				
				if (story.start == e.currentTarget.id)
					$('#isFirstParagraph').attr('disabled', 'disabled');
				console.log(paragraph.isEnd);
				$('#isEnd').attr('checked', (paragraph.isEnd == "true" || paragraph.isEnd == true));
				
				$('#addParagraphModal').modal();
			});
			
			/***Link creation***/
			$.each(story.paragraphes, function(i, e) {
			
				$.each(e.links, function(index, el) {

					jsPlumb.connect({
						source: el.origin, 
						target: el.destination, 
						parameters: {
							"lid": el.id
						}
					});
					
					/**for rendering with d3js**/
					links.push({
						source: el.origin,
						target: el.destination
					});
					/****************************/
				});
			});
			/******************/
			/******************/
			
			/***Interactives buttons outside the canvas**/
			
			$('#save').on('click', function() {
			
				story.paragraphes.forEach(function(el, i) {
				
					el.x = $('div.w#' + el.id).position().left;
					el.y = $('div.w#' + el.id).position().top;
				});
				
				story.update(function(status) {
				
					if (status)
						alert('Changes saved');
					else
						alert('An error has occured, please refresh the page and try again');
				});
			});
			
			$('.addParagraph').bind('click', function() {
				
				var pid = $('#addParagraphModal').attr('data-pid');
				console.log(pid);
				var paragraph = story.getParagraph(pid);
				if ($('#isFirstParagraph').attr('checked'))
					story.setStart(pid);
				paragraph.text = CKEDITOR.instances['newParagraph'].getData();
				$('.w#'+pid+'>.tooltip').html(paragraph.text);
				paragraph.isEnd = $('#isEnd').is(':checked');
				story.update();
			});
			
			$('.addLink').bind('click', function() {
			
				var source = $('#addLinkModal').attr('data-sourceid');
				var target = $('#addLinkModal').attr('data-targetid');
				var paragraph = story.getParagraph(source);
				var lid = $('#addLinkModal').attr('data-lid');
				var operation = $('.selectOperation').attr('value');
				var newValue = $('.newValue').val();
				var prop = $('#addOperation').children('.key').text();
				
				if (document.getElementById('choice').value == '') {
				
					alert('You have to put a text');
					return;
				}
				
				action = {
					key:prop,
					operation:operation,
					value:newValue			
				};
				
				if (lid)
				{
					var link = paragraph.getLink(lid);
					link.text = $('#choice').val();
					link.action = action;
					story.update();
					$('#addLinkModal').modal('hide');
				}
				else
					paragraph.addLink(source, target, $('#choice').val(), action, function(status, link) {
					
						if (status)
						{
							paragraph.links.push(link);
							var conn = jsPlumb.connect({source: source, target: target});
							conn.setParameters({lid:link.id});
							console.log(conn.getParameters('lid'));
							$('#addLinkModal').modal('hide');
						}
						else
							alert('An error has occured, please refresh the page and retry.');
					});
			});
			
			$('.deleteLinkButton').bind('click', function() {
		
				var pid = $('#addLinkModal').attr('data-pid');;
				var lid = $('#addLinkModal').attr('data-lid');;
				console.log(lid);
				var paragraph = story.getParagraph(pid);
				
				var linkBu = paragraph.getLink(lid);
				
				if (paragraph.removeLink(lid))
				{		
					story.update(function(status) {
					
						if (status < 1)
						{
							paragraph.links.push(linkBu);
							alert('Something went wrong, please retry.');
							return false;
						}
						
						jsPlumb.select({source: pid}).each(function(conn) {
							if (conn.getParameter('lid') == lid)
								jsPlumb.detach(conn);
						});
						return true;
					});
				}
				else
				{
					alert('Something went wrong, please retry.');
					return false;
				}
			});
			
			$('.updateMainCharStats').bind('click', function() {
				
				var properties = [];
				$('.mainCharStat > .input-prepend').each(function(i) {
					if ($(this).children('.key').val() != "")
						properties[i] = {key:$(this).children('.key').val(), value:$(this).children('.value').val()}
				});
				properties.push({key: 'main', value: 'true'});
				var mainCharacter = story.getMainCharacter();
				
				if (mainCharacter)
				{
					$.ajax({
					
						url: BASE_URL + 'index.php/edit/addCharProperties/' + mainCharacter.id,
						type: 'POST',
						data: {properties:properties, sid:$(this).attr('id')},
						dataType: 'json',
						beforeSend: function() {
						},
						success: function(data) {
							
							console.log(data);
							if (data.status)
							{
								var main = story.getMainCharacter();
								if (main)
								{
									main.properties = {};
									console.log(properties);
									for (var i = 0; i < properties.length; i++)
										main.properties[properties[i].key] = properties[i].value;
								}
								else
									alert('An error has occured, please refresh the page and retry.');
							}
							else
								alert('An error has occured, please refresh the page and retry.');
						},
						error: function(a, b, c) {
							console.log(a);
							console.log(b);
							console.log(c);
						}		
					});
				}
				else
					alert('An error has occured, please refresh the page and retry.');
			});
			
			$('#addNewChar').live('click', function() {
				
				$('.editOtherChar').empty();
				$('.otherCharSelector').attr('value', -1);
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
					if ($(this).children('.key').val() != "")
						properties[i] = {key:$(this).children('.key').val(), value:$(this).children('.value').val()}
				});
				properties.push({key: 'main', value: 'false'});
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
						if (data.status)
						{
							var old = story.characters.length;
							var name = "";
							for (var i = 0; i < properties.length; i++)
								if (properties[i].key == 'name')
									name = properties[i].value;
							
							if (name == "")
								return;
								
							var char = story.getCharByName(name);
							if (!char)
								char = story.addCharacter(false, {name: name});

							char.properties = {};
							
							if (char.id == 0)
								char.id = data.id.$id;
							
							for (var i = 0; i < properties.length; i++)
								char.properties[properties[i].key] = properties[i].value;
							
							if (old != story.characters.length)
							{
								var selector_html = '<option value="' + char.id + '">' + char.properties.name + '</option>"';
								$('.otherCharSelector').append(selector_html);
							}
						}
						else
							alert('An error has occured, please refresh the page and retry.');
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
				$('.editOtherChar').empty();
				$('.editOtherChar').append('<hr/>');
				$('.editOtherChar').append('<h5>Properties</h5>');
				console.log(cid);
				var char = story.getCharacter(cid);
				console.log(char);
				for (var property in char.properties)
				{
					if (property != 'main')
					{
						var html = '<div class="input-prepend">\
										Name: <input type="text" value="' + property + '" class="key span4" />\
										Value: <input type="text" value="' + char.properties[property] + '" class="value span4" />\
										<a class="btn" href="#" id="addStat"><i class="icon-plus"></i></a>\
										<a class="btn" href="#" id="rmStat"><i class="icon-minus"></i></a>\
									</div>';
						$('.editOtherChar').append(html);
					}
				};				
			});
			
			$('#newNode').live('click', function() {
			
				var isStart = (story.paragraphes.length?false:true);
				var isEnd = false;
				var content = 'A new paragraph !';
				
				story.addParagraph(isStart, isEnd, content, [], function(status, paragraph) {
					
					if (!status)
					{
						alert('Something went wrong, please retry.');
						return;
					}
					
					if (isStart)
						story.start = paragraph.id;
						
					story.paragraphes.push(paragraph);
					var html = '<div class="w" style="position:relative;left:50px;bottom:50px;" id="' + paragraph.id + '">\
									<div class="tooltip" style="display:none;">'
										+ paragraph.text +
									'</div>\
									<div class="ep">\
									</div>\
								</div>';
					$('#canvas').append(html);

					jsPlumb.draggable(jsPlumb.getSelector(".w"));
					$('#'+paragraph.id+'>.ep').each(function(i, e) {
			
						var p = $(e).parent();
						jsPlumb.makeSource($(e), {
						
							parent: p,
							anchor: "Continuous",
							connector: [ "StateMachine", { curviness:20 } ],
							connectorStyle:{ strokeStyle:nextColour(), lineWidth:2 }
						});
					});
			
					jsPlumb.makeTarget(jsPlumb.getSelector("#"+paragraph.id), {
					
						dropOptions:{ hoverClass:"dragHover" },
						anchor:"Continuous"	,
						beforeDrop: function(conn) {
							
							var modal = $('#addLinkModal');
							$('#choice').attr('value', '');
							$('#newValue').attr('value', '');
							modal.attr('data-lid', '');								
							modal.attr('data-conn-id', conn.connection.id);					
							modal.attr('data-sourceid', conn.sourceId);
							modal.attr('data-targetid', conn.targetId);
							
							$('#addLinkModal').modal('show');
							return false;
						}
					});
					
					$('.w').on('click', function() {
				
						$('.w').css({
							'border':'1px solid #346789'
						});
						
						$(this).css({
							'border': 'red 1px solid'
						});
						
						selected = $(this).attr('id');
						
						$(document).keyup(function(e) {
						
							if (e.keyCode == 46)
							{
								if (!selected)
									return;
									
								var pid = selected;
								story.removeParagraph(pid);
								story.update(function(status) {
								
									if (status)
									{
										jsPlumb.detachAllConnections(pid);
										$('.w#' + pid).remove();
									}
								});
							}
						});
					});
					
					$('.w').on('dblclick', function(e) {
						
						CKEDITOR.instances['newParagraph'].setData($(this).children('.tooltip').text());
						$('#addParagraphModal').css('visibility','visible');
						
						$('#addParagraphModal').attr('data-pid', e.currentTarget.id);
						
						var paragraph = story.getParagraph(e.currentTarget.id);
						console.log(paragraph);
						console.log(story);
						
						if (story.paragraphes.length > 1 && $('.w').length > 1)
							$('#isFirstParagraph').removeAttr('disabled');
							
						$('#isEnd').attr('checked', false);
						$('#isFirstParagraph').attr({
						
							'checked': story.start == e.currentTarget.id || story.paragraphes.length == 1
						});
						
						$('#isEnd').attr('checked', paragraph.isEnd);
						
						if (story.start == e.currentTarget.id)
							$('#isFirstParagraph').attr('disabled', 'disabled');
						
						$('#addParagraphModal').modal();
					});
				});
			});
			/*******************************/
			
			/***Rendering***/
			/*
			links.forEach(function(link) {
				
				link.source = nodes[link.source] || (nodes[link.source] = {name: link.source});
				link.target = nodes[link.target] || (nodes[link.target] = {name: link.target});
			});
			
			$.each(nodes, function(i, node) {
				
				node.data = {
					text: $('#canvas > .w#' + node.name).text()
				};
			});
			
			var canvas = $('#canvas');
			
			var svg = d3.select(document.getElementById('svg'))
				.append('svg');
			
			var force = d3.layout.force()
				.gravity(0.1)
				.distance(400)
				.charge(1)
				.on('tick', tick);
			
			force.nodes(d3.values(nodes))
				.links(d3.values(links));
				//.start();
			
			var link = svg.selectAll(".link").data(force.links());

			link.enter().append("line");
			
			link.exit().remove();
			
			$('#redraw').on('click', function() {
			
				force.start();
			});
			
			var node = svg.selectAll(".node").data(force.nodes());
			
			node.enter().append("svg:image")
				.attr("width", "0px")
				.attr("height", "0px");
		 
			node.exit().remove();
			
			function tick()
			{
			
				$('#tick').show();
				
				link.attr("x1", function(d) { return d.source.x; })
					.attr("y1", function(d) { return d.source.y; })
					.attr("x2", function(d) { return d.target.x; })
					.attr("y2", function(d) { return d.target.y; });
				
				node.attr("transform", function(d) { 
						
					$('#canvas > #' + d.name).css({
						'top': d.y,
						'left': d.x
					});
					
					return "translate(" + d.x + "," + d.y + ")"; 
				});
				jsPlumb.repaintEverything();
			}*/
			/***********************************/
		});
	});
});