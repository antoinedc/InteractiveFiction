(function() {
	
	var addLinkCmd = {
	
		modes:{wysiwyg:1,source:0 },
		readOnly: 1,
		exec: function (editor) {

			$('#addLinkModal').modal('show');
		}
	};
	
	var pluginName = 'addLink';
	CKEDITOR.plugins.add('addLink',
	{
		icons: 'addLink',
		init: function(editor)
		{
			//CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/save.js');
			var command = editor.addCommand( pluginName, addLinkCmd );
			editor.ui.addButton && editor.ui.addButton( 'AddLink', {
				label: 'Add a link',
				command: pluginName,
				toolbar: 'insert,10'
			});
		}
	});
	
})();