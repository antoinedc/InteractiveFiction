(function() {

	var savecCmd = {
		
		modes:{wysiwyg:1,source:1 },
		readOnly: 1,
		exec: function (editor) {
			
			$.ajax({
			
				url: BASE_URL + 'index.php/edit/updateParagraph/' + $('.generateHtml').attr('id'),
				type: 'POST',
				dataType: 'json',
				data: {pid:editor.element.$.id, text:editor.element.$.innerHTML},
				success: function(data) {
					if (data.status > 0)
						alert('Changes saved');
					else
						alert('Error, please try again');
				},
				error: function (a, b, c) {
					console.log(a);
					console.log(b);
					console.log(c);
				}
			});			
		}
	};

	var pluginName = 'savec';
	CKEDITOR.plugins.add('savec',
	{
		icons: 'save',
		init: function(editor)
		{
//			CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/save.js');
			var command = editor.addCommand( pluginName, savecCmd );
			editor.ui.addButton && editor.ui.addButton( 'Save', {
				label: 'Save changes',
				command: pluginName,
				toolbar: 'insert,10'
			});
			
			if (editor.addMenuItem) {
				editor.addMenuGroup('contextmenuGroup');
				
				editor.addMenuItem('savec', {
					label: 'Add a link',
					command: 'savecCmd',
					group: 'contextmenuGroup'
				});
			}
			
			if (editor.contextMenu) {
				editor.contextMenu.addListener(function(element, selection) {
					console.log(element);
					return { savec: CKEDITOR.TRISTATE_ON };
				});
			}
		}
	});
})();