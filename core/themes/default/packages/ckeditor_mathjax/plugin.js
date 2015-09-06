CKEDITOR.plugins.add('mathjax', {
	init: function( editor ) {
		var http = ('https:' == document.location.protocol ? 'https://' : 'http://');

		CKEDITOR.scriptLoader.load( [
			http+'latex.codecogs.com/js/eq_config.js',
			http+'latex.codecogs.com/js/eq_editor-lite-16.js',
		]);
		var fileref=document.createElement("link");
		fileref.setAttribute("rel", "stylesheet");
		fileref.setAttribute("type", "text/css");
		fileref.setAttribute("href", http+'latex.codecogs.com/css/equation-embed.css');
		document.getElementsByTagName("head")[0].appendChild(fileref)

		editor.addCommand('mathjaxDialog', new CKEDITOR.dialogCommand('mathjaxDialog'), {
			allowedContent: 'div[class="math-area"]',
			requiredContent: 'div[class="math-area"]'
		});

		editor.ui.addButton('MathJax Dialog', {
			label: 'Open LaTeX/CodeCogs Input Dialog',
			command: 'mathjaxDialog', toolbar: 'others',
			icon: CKEDITOR.plugins.getPath('mathjax') + 'icons/fvonx.png'
		})

		editor.on( 'doubleclick', function(evt) {
			var element = evt.data.element;
			if (element && element.is('p') && element.hasClass('math-area')) {
				var latex = element.$.innerHTML;
				if (latex) {
					evt.data.dialog = 'mathjaxDialog';
					evt.cancelBubble = true;
					evt.returnValue = false;
					evt.stop();
				}
			}
		}, null, null, 1);
	}
});

CKEDITOR.dialog.add('mathjaxDialog', CKEDITOR.plugins.getPath('mathjax') + 'dialogs/mathjax.js')
