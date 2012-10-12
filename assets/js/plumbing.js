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
		
		this.getParagraph = function(pid) {
		
			var res = false;
			for (var i = 0; i < this.paragraphes.length; i++)
				if (this.paragraphes[i].id == pid)
					res = this.paragraphes[i];
			
			return false;
		};
		
		this.removeParagraph = function(pid) {
		
			for (var i = 0; i < this.paragraphes.length; i++)
				if (this.paragraphes[i].id == pid)
				{
					this.paragraphes = this.paragraphes.slice(i, i+1);
					break;
				}
			
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
		
		this.addParagraph = function(isEnd, isStart, text, links, callback) {
		
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
					if (callback) 
						callback(true, newParagraph);
				}
				else
					if (callback)
						callback(false);
			}, 'json');
		};
		
		this.addCharacter = function(main, properties, callback) {
		
			var newCharacter = new Character(0);
			newCharacter.main = main;
			newCharacter.properties = properties,
			
			$.post(BASE_URL + 'index.php/edit/addCharProperties/' + (main ? 0 : -1), properties, function(data) {
			
				if (data.status > 0)
				{
					newCharacter.id = data.id;
					this.characters.push(newCharacter);
					if (callback)
						callback(true, newCharacter);
				}
				else
					if (callback)
						callback(false);
			}, 'json');
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
			for (var character in this.characters)
				if (character.id == id)
				{
					res = character;
					break;
				}
			return res;
		};
	};
	
	var Paragraph = function(id) {
	
		this.id = id;
		this.sid = 0;
		this.isEnd = false;
		this.isStart = false;
		this.text = '';
		this.links = [];
		
		/*this.addLink = function(source, target, text, action, callback) {
		
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
					newLink.id = data.id;
					this.links.push(newLink);
					if (callback)
						callback(true, newLink);
				}
				else
					callback(false);
			}, 'json');
		};*/
		
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
				if (this.links[j].id == lid);
					this.links = this.links.slice(j, j+1);
			
			console.log(this.links);
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
	
		this.id = 0;
		this.main = false;
		this.properties = [] /**A property is a json object: {key:'age', value:'22'} no need to define another class for this**/
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
			
			var canvas = document.getElementById('canvas');
			var translatePos = {
				x: canvas.width / 2,
				y: canvas.height / 2
			};
			
			var nodes = {};
			var links = [];
			
			/**Canvas init**/
			/*$('#canvas').css('cursor', 'url(https://mail.google.com/mail/images/2/openhand.cur), default');
		
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
			});	*/	
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
					
					/**to change**/
					var modal = $('#addLinkModal');
					
					modal.attr('data-conn-id', conn.connection.id);
					modal.attr('data-lid', '');
					
					modal.find('.modal-body').find('#linkOthers').find('.linkSource').attr('id', conn.sourceId);
					modal.find('.modal-body').find('#linkOthers').find('.linkTarget').attr('id', conn.targetId);
					
					$('#addLinkModal').modal('show');
					
					return true;
				}			
			});
			
			jsPlumb.bind("jsPlumbConnection", function(conn) {
					
				conn.connection.setPaintStyle({strokeStyle:nextColour()});
				conn.connection.bind('click', function(conn) {
					
					/**TODO: improve the way we pass these ids**/
					var modal = $('#addLinkModal');
					modal.attr('data-lid', conn.getParameter('lid'));
					modal.attr('data-pid', conn.sourceId);
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
				console.log(e);
				CKEDITOR.instances['newParagraph'].setData($(this).children('.tooltip').text());
				$('#addParagraphModal').css('visibility','visible');
				
				$('#addParagraphModal').attr('data-pid', e.currentTarget.id);
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
			
			/***interactives buttons outside the canvas**/
			
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

			$('#newNode').live('click', function() {
			
				var isFirst = false;
				var isEnd = false;
				var content = 'A new paragraph !';
				
				story.addParagraph(isFirst, isEnd, content, [], function(status, paragraph) {
					
					if (!status)
					{
						alert('Something went wrong, please retry.');
						return;
					}
					
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
							
							/**to change**/
							var modal = $('#addLinkModal');
							modal.attr('data-conn-id', conn.id);
							modal.attr('data-lid', '');
							
							modal.find('.modal-body').find('#linkOthers').find('.linkSource').attr('id', conn.sourceId);
							modal.find('.modal-body').find('#linkOthers').find('.linkTarget').attr('id', conn.targetId);
							
							$('#addLinkModal').modal('show');
							return true;
						}
					});
					
					$('.w').on('dblclick', function(e) {
						
						CKEDITOR.instances['newParagraph'].setData($(this).children('.tooltip').text());
						$('#addParagraphModal').css('visibility','visible');
						console.log(paragraph.id);
						$('#addParagraphModal').attr('data-pid', e.currentTarget.id);
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