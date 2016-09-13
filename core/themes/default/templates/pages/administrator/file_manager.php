<div class="container">
	<div class="row">
		<div class="col-md-3 col-sm-3">
			<form class="admin-form" method="post" id="file-upload" enctype="multipart/form-data">
				<strong><?php echo $text_upload_images; ?></strong>
				<select name="num_images" id="num_images" class="form-control" onchange="update_uploads();">
					<?php for($i=1; $i<=10; $i++) {
						echo "<option value=\"$i\">$i</option>";
					} ?>
				</select>
				<br />
				<div id="image_uploads"></div>
				<button class="btn btn-primary" type="submit" name="file-upload-submit"><?php echo $text_upload_images; ?></button>
			</form>
		</div>
		<div class="col-md-9 col-sm-9">
			<br />
			<strong><?php echo $text_current_images; ?>:</strong> <?php echo $path; ?>
			<form class="admin-form" method="post" id="file-delete" enctype="multipart/form-data">
				<button class="btn btn-primary" type="submit" name="file-delete-submit"><?php echo $text_delete; ?></button>
				<div class="row file-grid">
					<?php foreach (glob($path.'*') as $file) { ?>
						<?php if (is_dir($file)) { ?>

					<?php } elseif (is_file($file)) { ?>
							<div class="col-md-4 col-sm-6 align-center">
								<label>
									<img class="img-responsive" src="/<?php echo $file; ?>" />
									<input type="checkbox" name="delete_files[]" value="<?php echo $file; ?>" />
									<strong><?php echo $text_delete; ?></strong>
									<br />
									<a href="/<?php echo $file; ?>" target="_blank"><?php echo $text_open; ?>: <?php echo basename($file); ?></a>
								</label>
							</div>
						<?php } ?>
					<?php } ?>
				</div>
				<button class="btn btn-primary" type="submit" name="file-delete-submit"><?php echo $text_delete; ?></button>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
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
	update_uploads();
</script>
