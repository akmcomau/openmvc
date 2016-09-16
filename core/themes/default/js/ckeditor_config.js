CKEDITOR.stylesSet.add( 'my_styles', [
	{ name: 'Responsive Image', element: 'img', attributes: { 'class': 'img-responsive' } },
]);

CKEDITOR.editorConfig = function( config ) {
	config.toolbarGroups = [
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'links' },
		{ name: 'insert'},
		{ name: 'forms' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'others' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'colors', 'cleanup' ] },
		{ name: 'styles' },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
	];

	if (typeof(MathJax) == 'undefined') {
		config.extraPlugins = 'widgetbootstrap,font,onchange,colorbutton,youtube,bgimage';
	}
	else {
		config.extraPlugins = 'widgetbootstrap,font,onchange,colorbutton,youtube,bgimage,mathjax';
	}

	config.stylesSet = 'my_styles';
	config.extraAllowedContent = '*(*);*{*}';
	config.protectedSource.push(/<\?php*?\?>/g);
	config.format_tags = 'p;h1;h2;h3;h4;h5;h6;pre;address;div';
};
