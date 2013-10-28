CKEDITOR.dialog.add( 'mathjaxDialog', function( editor ) {
	return {
		title : 'Math Input Dialog',
		minWidth : 800,
		minHeight : 400,
		contents: [
			{
				id: 'mathjax-tab',
				label: 'CodeCogs Editor',
				elements:[
					{
						type: 'html',
						html: '<div id="math-editor"></div><div id="toolbar"></div><textarea id="latexInput" class="cke_dialog_ui_input_textarea" rows="5"></textarea><br /><br /><strong>Preview</strong><br /><div><img id="equation" /></div>',
						setup: function (element) {
							EqEditor.embed('toolbar','','mini','en-us');
							EqEditor.add(new EqTextArea('equation', 'latexInput'),false);
						}
					}
				]
			}
		], onLoad: function() {
			this.setupContent(editor.document.createText(""))
		}, onShow: function() {
			var mySelection = editor.getSelection();

			if (CKEDITOR.env.ie) {
				mySelection.unlock(true);
				selectedText = mySelection.getNative().createRange().text;
			} else {
				selectedText = mySelection.getNative();
			}

			EqEditor.clearText();

			if ($.trim(selectedText.focusNode.data) != '') {
				console.log(selectedText.focusNode.data)
				var regex = /^\s*\$(.*)\$\s*$/;
				var match = regex.exec(selectedText.focusNode.data);
				if (match){
					selectedText = match[1];
				}
				else {
					selectedText = '';
				}
			}

			$('#latexInput').val(selectedText);
			EqEditor.setFormat('gif');
			EqEditor.setFormat('png');

		}, onOk: function () {
			var dialog = this;
			var mathjaxInput = $('#latexInput').val();

			// this element is our mathjax-source container
			var content = editor.document.createElement('p');
			content.setAttribute('class', 'math-area');
			content.setText('$ '+mathjaxInput+' $');
			editor.insertElement(content);

		}, onHide: function() {
			MathJax.Hub.Typeset()
		}, onCancel: function() {
			MathJax.Hub.Typeset()
		}
	}
});
