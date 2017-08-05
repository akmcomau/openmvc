var __hasProp = {}.hasOwnProperty;
var __extends = function(child, parent) {
	for (var key in parent) {
		if (__hasProp.call(parent, key))
			child[key] = parent[key];
	}

	function ctor() {
		this.constructor = child;
	}

	ctor.prototype = parent.prototype;

	child.prototype = new ctor();
	child.__super__ = parent.prototype;

	return child;
};

ContentTools.Tools.Button = (function(_super) {
	__extends(Button, _super);

	// This class extends the existing Bold tool
	function Button() {
	  return Button.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(Button, 'button');

	// Set the tool tip that will appear
	Button.label = 'Button';

	// Set the name of the icon (this wont exist unless you add one)
	Button.icon = 'button';

	// Set the tag that will be used to wrap content when pressing this tool
	Button.tagName = 'button';

	return Button;

})(ContentTools.Tools.Bold);

ContentTools.Tools.Sub = (function(_super) {
	__extends(Sub, _super);

	// This class extends the existing Bold tool
	function Sub() {
	  return Sub.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(Sub, 'sub');

	// Set the tool tip that will appear
	Sub.label = 'SubScript';

	// Set the name of the icon (this wont exist unless you add one)
	Sub.icon = 'sub';

	// Set the tag that will be used to wrap content when pressing this tool
	Sub.tagName = 'sub';

	return Sub;

})(ContentTools.Tools.Bold);

ContentTools.Tools.Sup = (function(_super) {
	__extends(Sup, _super);

	// This class extends the existing Bold tool
	function Sup() {
	  return Sup.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(Sup, 'sup');

	// Set the tool tip that will appear
	Sup.label = 'SuperScript';

	// Set the name of the icon (this wont exist unless you add one)
	Sup.icon = 'sup';

	// Set the tag that will be used to wrap content when pressing this tool
	Sup.tagName = 'sup';

	return Sup;

})(ContentTools.Tools.Bold);

ContentTools.Tools.Small = (function(_super) {
	__extends(Small, _super);

	// This class extends the existing Bold tool
	function Small() {
	  return Small.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(Small, 'small');

	// Set the tool tip that will appear
	Small.label = 'SmallScript';

	// Set the name of the icon (this wont exist unless you add one)
	Small.icon = 'heading';

	// Set the tag that will be used to wrap content when pressing this tool
	Small.tagName = 'small';

	return Small;

})(ContentTools.Tools.Bold);

ContentTools.Tools.Big = (function(_super) {
	__extends(Big, _super);

	// This class extends the existing Bold tool
	function Big() {
	  return Big.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(Big, 'big');

	// Set the tool tip that will appear
	Big.label = 'BigScript';

	// Set the name of the icon (this wont exist unless you add one)
	Big.icon = 'heading';

	// Set the tag that will be used to wrap content when pressing this tool
	Big.tagName = 'big';

	return Big;

})(ContentTools.Tools.Bold);

ContentTools.Tools.H1 = (function(_super) {
	__extends(H1, _super);

	// This class extends the existing Bold tool
	function H1() {
	  return H1.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(H1, 'h1');

	// Set the tool tip that will appear
	H1.label = 'H1Script';

	// Set the name of the icon (this wont exist unless you add one)
	H1.icon = 'heading';

	// Set the tag that will be used to wrap content when pressing this tool
	H1.tagName = 'h1';

	return H1;

})(ContentTools.Tools.Bold);

ContentTools.Tools.H2 = (function(_super) {
	__extends(H2, _super);

	// This class extends the existing Bold tool
	function H2() {
	  return H2.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(H2, 'h2');

	// Set the tool tip that will appear
	H2.label = 'H2Script';

	// Set the name of the icon (this wont exist unless you add one)
	H2.icon = 'heading';

	// Set the tag that will be used to wrap content when pressing this tool
	H2.tagName = 'h2';

	return H2;

})(ContentTools.Tools.Bold);

ContentTools.Tools.H3 = (function(_super) {
	__extends(H3, _super);

	// This class extends the existing Bold tool
	function H3() {
	  return H3.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(H3, 'h3');

	// Set the tool tip that will appear
	H3.label = 'H3Script';

	// Set the name of the icon (this wont exist unless you add one)
	H3.icon = 'heading';

	// Set the tag that will be used to wrap content when pressing this tool
	H3.tagName = 'h3';

	return H3;

})(ContentTools.Tools.Bold);

ContentTools.Tools.H4 = (function(_super) {
	__extends(H4, _super);

	// This class extends the existing Bold tool
	function H4() {
	  return H4.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(H4, 'h4');

	// Set the tool tip that will appear
	H4.label = 'H4Script';

	// Set the name of the icon (this wont exist unless you add one)
	H4.icon = 'heading';

	// Set the tag that will be used to wrap content when pressing this tool
	H4.tagName = 'h4';

	return H4;

})(ContentTools.Tools.Bold);

ContentTools.Tools.H5 = (function(_super) {
	__extends(H5, _super);

	// This class extends the existing Bold tool
	function H5() {
	  return H5.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(H5, 'h5');

	// Set the tool tip that will appear
	H5.label = 'H5Script';

	// Set the name of the icon (this wont exist unless you add one)
	H5.icon = 'heading';

	// Set the tag that will be used to wrap content when pressing this tool
	H5.tagName = 'h5';

	return H5;

})(ContentTools.Tools.Bold);

ContentTools.Tools.H6 = (function(_super) {
	__extends(H6, _super);

	// This class extends the existing Bold tool
	function H6() {
	  return H6.__super__.constructor.apply(this, arguments);
	}

	// Stow the tool so we can reference it later using 'sup'
	ContentTools.ToolShelf.stow(H6, 'h6');

	// Set the tool tip that will appear
	H6.label = 'H6Script';

	// Set the name of the icon (this wont exist unless you add one)
	H6.icon = 'heading';

	// Set the tag that will be used to wrap content when pressing this tool
	H6.tagName = 'h6';

	return H6;

})(ContentTools.Tools.Bold);

window.addEventListener('load', function() {
	var editor;

	ContentTools.StylePalette.add([
		new ContentTools.Style('Table (Default)', 'table', ['table']),
		new ContentTools.Style('Responsive Image', 'img-responsive', ['img']),
		new ContentTools.Style('Button (Default)', 'btn', ['button']),
		new ContentTools.Style('Button (Primary)', 'btn-primary', ['button']),
		new ContentTools.Style('Button (Large)', 'btn-lge', ['button']),
		new ContentTools.Style('Responsive Row', 'row', ['ul', 'ol']),
		new ContentTools.Style('Cell, Width: 1', 'col-md-1', ['li']),
		new ContentTools.Style('Cell, Width: 2', 'col-md-2', ['li']),
		new ContentTools.Style('Cell, Width: 3', 'col-md-3', ['li']),
		new ContentTools.Style('Cell, Width: 4', 'col-md-4', ['li']),
		new ContentTools.Style('Cell, Width: 5', 'col-md-5', ['li']),
		new ContentTools.Style('Cell, Width: 6', 'col-md-6', ['li']),
		new ContentTools.Style('Cell, Width: 7', 'col-md-7', ['li']),
		new ContentTools.Style('Cell, Width: 8', 'col-md-8', ['li']),
		new ContentTools.Style('Cell, Width: 9', 'col-md-9', ['li']),
		new ContentTools.Style('Cell, Width: 10', 'col-md-10', ['li']),
		new ContentTools.Style('Cell, Width: 11', 'col-md-11', ['li']),
		new ContentTools.Style('Cell, Width: 12', 'col-md-12', ['li'])
	]);

	for (var i=ContentTools.DEFAULT_TOOLS.length-1; i>=0; i--) {
		var found = false;
		for (var j=ContentTools.DEFAULT_TOOLS[i].length-1; j>=0; j--) {
			if (
				ContentTools.DEFAULT_TOOLS[i][j] === 'heading' ||
					ContentTools.DEFAULT_TOOLS[i][j] === 'subheading'
			) {
				ContentTools.DEFAULT_TOOLS[i].splice(j, 1);
				found = true;
			}
		}
		if (found) {
			ContentTools.DEFAULT_TOOLS[i].unshift('sub');
			ContentTools.DEFAULT_TOOLS[i].unshift('sup');
			ContentTools.DEFAULT_TOOLS[i].unshift('button');
			ContentTools.DEFAULT_TOOLS[i].unshift('small');
			ContentTools.DEFAULT_TOOLS[i].unshift('big');
			ContentTools.DEFAULT_TOOLS[i].unshift('h6');
			ContentTools.DEFAULT_TOOLS[i].unshift('h5');
			ContentTools.DEFAULT_TOOLS[i].unshift('h4');
			ContentTools.DEFAULT_TOOLS[i].unshift('h3');
			ContentTools.DEFAULT_TOOLS[i].unshift('h2');
			ContentTools.DEFAULT_TOOLS[i].unshift('h1');
		}
	}

	editor = ContentTools.EditorApp.get();
	editor.init('*[data-editable]', 'data-name');

	editor.addEventListener('saved', function (ev) {
		var name, payload, regions, xhr;

		// Check that something changed
		regions = ev.detail().regions;
		if (Object.keys(regions).length == 0) {
			return;
		}

		// Set the editor as busy while we save our changes
		this.busy(true);

		// Collect the contents of each region into a FormData instance
		payload = new FormData();
		for (name in regions) {
			if (regions.hasOwnProperty(name)) {
				payload.append(name, regions[name]);
			}
		}

		// Send the update content to the server to be saved
		function onStateChange(ev) {
			// Check if the request is finished
			if (ev.target.readyState == 4) {
				editor.busy(false);
				if (ev.target.status == '200') {
					// Save was successful, notify the user with a flash
					new ContentTools.FlashUI('ok');
				} else {
					// Save failed, notify the user with a flash
					new ContentTools.FlashUI('no');
				}
			}
		};

		xhr = new XMLHttpRequest();
		xhr.addEventListener('readystatechange', onStateChange);
		xhr.open('POST', edit_url);
		xhr.send(payload);
	});

});
