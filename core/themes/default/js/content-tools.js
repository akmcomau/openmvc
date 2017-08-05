window.addEventListener('load', function() {
    var editor;

	ContentTools.StylePalette.add([
		new ContentTools.Style('Table (Default)', 'table', ['table'])
	]);

	editor = ContentTools.EditorApp.get();
	editor.init('*[data-editable]', 'data-name');

	editor.addEventListener('start', function (ev) {
		$('body').append('<button id="make-editable-btn" class="btn btn-primary" style="position: absolute; top: 20px; left: 150px;" onclick="MakeEditable();">Make All Text Editable</button>');
		MakeEditable();
	});

	editor.addEventListener('stop', function (ev) {
		$('#make-editable-btn').remove();
	});

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
				payload.append(name, $('#'+name).html());
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


function MakeEditable() {
	$('#main-content *').removeClass('ce-element--type-static');
	$('#main-content h1').attr('contenteditable', '');
	$('#main-content h2').attr('contenteditable', '');
	$('#main-content h3').attr('contenteditable', '');
	$('#main-content h4').attr('contenteditable', '');
	$('#main-content h5').attr('contenteditable', '');
	$('#main-content h6').attr('contenteditable', '');
	$('#main-content h7').attr('contenteditable', '');
	$('#main-content p').attr('contenteditable', '').addClass('ce-element ce-element--type-text');
	$('#main-content div').attr('contenteditable', '');
	$('#main-content img').attr('contenteditable', '');
	$('#main-content table').attr('contenteditable', '');
}
