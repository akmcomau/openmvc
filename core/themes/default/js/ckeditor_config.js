CKEDITOR.stylesSet.add( 'my_styles', [
	{ name: 'Responsive Image', element: 'img', attributes: { 'class': 'img-responsive' } },
]);

CKEDITOR.editorConfig = function( config ) {
	config.toolbarGroups = [
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'forms' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'others' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'styles' },
		{ name: 'colors' },
	];

	if (typeof(MathJax) == 'undefined') {
		config.extraPlugins = 'widgetbootstrap,font,onchange';
	}
	else {
		config.extraPlugins = 'widgetbootstrap,font,onchange,mathjax';
	}

	config.stylesSet = 'my_styles';
	config.extraAllowedContent = '*(*)';
	config.protectedSource.push(/<\?[\s\S]*?\?>/g);
	config.protectedSource.push(/\r|\n/g);
	config.removeButtons = 'Underline,Subscript,Superscript';
	config.format_tags = 'p;h1;h2;h3;pre';
	config.removeDialogTabs = 'image:advanced;link:advanced';
};
