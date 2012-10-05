$(function() {
	
	if (!$('#onEditStory').length)
		return false;	
	
		
	var curColourIndex = 1, maxColourIndex = 24, nextColour = function() {
		var R,G,B;
		R = parseInt(128+Math.sin((curColourIndex*3+0)*1.3)*128);
		G = parseInt(128+Math.sin((curColourIndex*3+1)*1.3)*128);
		B = parseInt(128+Math.sin((curColourIndex*3+2)*1.3)*128);
		curColourIndex = curColourIndex + 1;
		if (curColourIndex > maxColourIndex) curColourIndex = 1;
		return "rgb(" + R + "," + G + "," + B + ")";
	};
	
	var p;
	jsPlumb.ready(function() {
		
		var nodes;
		var sid = $('#onEditStory').attr('data-story-id');
			
		var canvas = document.getElementById('canvas');
		
        var translatePos = {
			x: canvas.width / 2,
			y: canvas.height / 2
        };
		
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
		
		$.getJSON(BASE_URL + 'index.php/edit/getStory/' + sid, function(data) {
			console.log(data);
			if (data.status > 0)
			{
				
				jsPlumb.importDefaults({
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
				});
				jsPlumb.setRenderMode(jsPlumb.CANVAS);
				
				jsPlumb.draggable(jsPlumb.getSelector(".w"));
				
				var links = [];
				var nodes = {};
				
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
						
						var modal = $('#addLinkModal');
						modal.attr('data-lid', conn.getParameter('lid'));
						modal.find('.modal-body').find('#linkOthers').find('.linkSource').attr('id', conn.sourceId);
						modal.find('.modal-body').find('#linkOthers').find('.linkTarget').attr('id', conn.targetId);
						
						$('#addLinkModal').modal('show');
					});
				});
				
				$('#newNode').live('click', function() {
				
					var isFirst = false;
					var isEnd = false;
					var content = 'A new paragraph !';
					
					var data = {
						
						sid: sid,
						isFirst: isFirst,
						isEnd: isEnd,
						content: content
					};
					
					$.post(BASE_URL + 'index.php/edit/addParagraph', data, function(data) {
					
						console.log(data);
						if (data.status > 0)
						{
							var html = '<div class="w" style="position:relative;left:50px;bottom:50px;" id="' + data.id.$id + '">\
											<div class="tooltp" style="display:none;">'
												+ content +
											'</div>\
											<div class="ep">\
											</div>\
										</div>';
							$('#canvas').append(html);
							
							jsPlumb.draggable(jsPlumb.getSelector("#"+data.id.$id));
							$('#'+data.id.$id+'>.ep').each(function(i, e) {
					
								var p = $(e).parent();
								jsPlumb.makeSource($(e), {
								
									parent: p,
									anchor: "Continuous",
									connector: [ "StateMachine", { curviness:20 } ],
									connectorStyle:{ strokeStyle:nextColour(), lineWidth:2 }
								});
							});
					
							jsPlumb.makeTarget(jsPlumb.getSelector("#"+data.id.$id), {
							
								dropOptions:{ hoverClass:"dragHover" },
								anchor:"Continuous"	,
								beforeDrop: function(conn) {
										
									var modal = $('#addLinkModal');
									modal.attr('data-conn-id', conn.id);
									modal.attr('data-lid', '');
									
									modal.find('.modal-body').find('#linkOthers').find('.linkSource').attr('id', conn.sourceId);
									modal.find('.modal-body').find('#linkOthers').find('.linkTarget').attr('id', conn.targetId);
									
									$('#addLinkModal').modal('show');
									return true;
								}					
							});
							
							$('#'+data.id.$id).each(function(i) {
								
								$(this).append(parseInt($(this).attr('id').slice(-6)));
								$(this).on('dblclick', function() {
									
									CKEDITOR.instances['newParagraph'].setData($(this).children('.tooltip').text());
									$('#newParagraph').attr('data-pid', $(this).attr('id'));
									$('#addParagraphModal').modal();
								});
							});
						}
					}, 'json');
				});
				
				$('#newParagraph').css('visibility','visible');
				$('.w').each(function(i) {
					
					$(this).append(parseInt($(this).attr('id').slice(-6)));
					$(this).on('dblclick', function() {
						
						CKEDITOR.instances['newParagraph'].setData($(this).children('.tooltip').text());
						$('#newParagraph').attr('data-pid', $(this).attr('id'));
						$('#addParagraphModal').modal();
					});
				});
			
				$.each(data.data.paragraphes, function(i, e) {
			
					$.each(e.links, function(index, el) {
						
						jsPlumb.connect({
							source: el.origin, 
							target: el.destination, 
							parameters: {
								"lid": el._id.$id
							}
						});
							
						links.push({
							source: el.origin,
							target: el.destination
						});
					});
				});
				
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
					.links(d3.values(links))
					.start();
				
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
				}
			}
		});
	});

	var getExcerpt = function (text) {
	
		var result = '';
		var split = text.split(' ');
		for (var i = 0; i < 10; i++)
			result += split[i] + ' ';
		return result.slice(result.length - 1);
	};
});