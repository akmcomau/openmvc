<div class="container">
	<div class="row">
		<div class="col-md-3 col-sm-3">
			<form action="<?php $this->url->getUrl('administrator/FileManager', 'index', [], ['path' => $path_id, 'sub_path' => $sub_path]); ?>" class="admin-form" method="post" id="file-upload" enctype="multipart/form-data">
				<strong><?php echo $text_upload_files; ?></strong>
				<select name="num_images" id="num_images" class="form-control" onchange="update_uploads();">
					<?php for($i=1; $i<=10; $i++) {
						echo "<option value=\"$i\">$i</option>";
					} ?>
				</select>
				<br />
				<div id="image_uploads"></div>
				<button class="btn btn-primary" type="submit" name="file-upload-submit"><?php echo $text_upload_files; ?></button>
			</form>
			<br /><br />
			<button class="btn btn-primary" type="button" onclick="delete_selected();"><?php echo $text_delete_selected; ?></button>
			<br /><br />
			<button class="btn btn-primary" type="button" onclick="rename_selected()"><?php echo $text_rename_selected; ?></button>
			<!--<br /><br />
			<button class="btn btn-primary" type="button" onclick="move_selected()"><?php echo $text_move_selected; ?></button>-->
			<br /><br />
			<button class="btn btn-primary" type="button" onclick="new_folder();"><?php echo $text_new_folder; ?></button>
		</div>
		<div class="col-md-9 col-sm-9">
			<form id="path-select" method="get">
				<strong><?php echo $text_current_path; ?>:</strong>
				<select name="path" class="form-control" onchange="$('#path-select').submit();">
					<?php $counter = 0; ?>
					<?php foreach ($paths as $path => $type) { ?>
						<option value="<?php echo $counter; ?>" <?php if ($path_id == $counter++) echo 'selected="selected"'; ?>><?php echo $path; ?></option>
					<?php } ?>
				</select>
			</form>
			<form action="<?php echo $this->url->getUrl('administrator/FileManager', 'index', [], ['path' => $path_id, 'sub_path' => $sub_path]); ?>" class="admin-form" method="post" id="files-form" enctype="multipart/form-data">
				<input type="hidden" id="submit_type" name="submit_type" value="" />
				<input type="hidden" id="submit_value" name="submit_value" value="" />
				<div>
				</div>
				<?php if ($folder_type == 'images') { ?>
					<hr />
					<div class="row file-grid">
						<?php foreach (glob($glob_path.'*') as $file) { ?>
							<?php if (is_dir($file)) { ?>

							<?php } elseif (is_file($file)) { ?>
								<div class="col-md-4 col-sm-6 align-center">
									<label>
										<img class="img-responsive" src="/<?php echo $file; ?>" />
										<input type="checkbox" name="select_files[]" value="<?php echo $file; ?>" />
										<strong><?php echo $text_delete; ?></strong>
										<br />
										<a href="/<?php echo $file; ?>" target="_blank"><?php echo $text_open; ?>: <?php echo basename($file); ?></a>
									</label>
								</div>
							<?php } ?>
						<?php } ?>
					</div>
				<?php } else { ?>
					<br />
					<table class="table">
						<tr>
							<th><?php echo $text_filename; ?></th>
							<th><?php echo $text_mime_type; ?></th>
							<th><?php echo $text_filesize; ?></th>
							<th><?php echo $text_delete; ?></th>
						</tr>
						<?php foreach (glob($glob_path.'*') as $file) { ?>
							<?php if (is_dir($file)) { ?>
								<tr>
									<td><a href="<?php echo $this->url->getUrl('administrator/FileManager', 'index', [], ['path' => $path_id, 'sub_path' => $sub_path.'/'.basename($file)]); ?>"><?php echo basename($file); ?></a></td>
									<td><?php echo $text_directory; ?></td>
									<td>-</td>
									<td><input type="checkbox" name="select_folders[]" value="<?php echo $file; ?>" /></td>
								</tr>
							<?php } elseif (is_file($file)) { ?>
								<tr>
									<td><a href="" target="_blank"><?php echo basename($file); ?></a></td>
									<td><?php echo mime_content_type($file); ?></td>
									<td><?php echo number_format((int)(filesize($file)/1024), 0); ?> KB</td>
									<td><input type="checkbox" name="select_files[]" value="<?php echo $file; ?>" /></td>
								</tr>
							<?php } ?>
						<?php } ?>
					</table>
				<?php } ?>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
	<?php echo $message_js; ?>

	function update_uploads() {
		var num_images = $('#num_images').val();

		var images = [];
		$('#image_uploads').find('input').each(function() {
			images[images.length] = $(this);
		});

		$('#image_uploads').html('');
		for (var i=0; i<num_images; i++) {
			var input = $('<input type="file" />');
			if (typeof(images[i]) != 'undefined') {
				input = images[i];
			}
			else {
				input.attr('name', 'image[]');
			}
			$('#image_uploads').append(input);
			$('#image_uploads').append($('<br />'));
		}
	}

	function delete_selected() {
		if (confirm('<?php echo addslashes($text_are_you_sure_delete) ?>')) {
			$('#submit_type').val('delete');
			$('#files-form').submit();
		}
	}

	function rename_selected() {
		var new_name = prompt('<?php echo addslashes($text_prompt_rename) ?>');
		if (new_name) {
			$('#submit_type').val('rename');
			$('#submit_value').val(new_name);
			$('#files-form').submit();
		}
	}

	function move_selected() {

	}

	function new_folder() {
		var folder = prompt('<?php echo addslashes($text_prompt_new_folder) ?>');
		if (folder) {
			$('#submit_type').val('new_folder');
			$('#submit_value').val(folder);
			$('#files-form').submit();
		}
	}

	update_uploads();
</script>
