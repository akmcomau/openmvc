<link href="http://latex.codecogs.com/css/equation-embed.css" rel="stylesheet" media="screen" />
<script src="http://latex.codecogs.com/js/eq_config.js"></script>
<script src="http://latex.codecogs.com/js/eq_editor-lite-16.js"></script>

<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form class="admin-form" method="post" id="form-block">
				<div class="widget">
					<div class="widget-header">
						<h3><?php
							if ($is_add_page) echo $text_add_header;
						 	else echo $text_update_header;
						?></h3>
					</div>
					<div class="widget-content">
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_title; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($block->title); ?>" />
								<?php echo $form->getHtmlErrorDiv('title'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_tag; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="tag" value="<?php echo htmlspecialchars($block->tag); ?>" />
								<?php echo $form->getHtmlErrorDiv('tag'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_category; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<select name="category">
									<option value=""></option>
									<?php foreach ($categories as $value => $text) { ?>
										<option value="<?php echo $value; ?>" <?php if ($block->getCategory() && $value == $block->getCategory()->id) echo 'selected="selected"'; ?>><?php echo $text; ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_content; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<textarea class="form-control ckeditor" name="content"><?php echo htmlspecialchars($block->content); ?></textarea>
								<?php echo $form->getHtmlErrorDiv('content'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-12 align-center">
								<button class="btn btn-primary" type="submit" name="form-block-submit"><?php
									if ($is_add_page) echo $text_add_button;
									else echo $text_update_button;
								?></button>
								<br /><br />
							</div>
						</div>
						<div class="row default-padding">
							<hr class="separator-2column" />
							<h2 class="align-center"><?php echo $text_preview; ?></h2>
							<hr class="separator-2column" />
							<div id="content-preview"></div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>

	function updatePreview(content) {
		$('#content-preview').html(content);
		MathJax.Hub.Queue(["Typeset", MathJax.Hub, 'content-preview']);
	}
	CKEDITOR.on('instanceCreated', function (e) {
		var editor = e.editor;
		editor.on('change', function (ev) {
			var content = e.editor.getData();
			updatePreview(content);
		});
	});
	updatePreview($('textarea[name="content"]').val());
</script>

