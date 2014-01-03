<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form method="post" id="form-block">
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
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_type; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<select name="type" class="form-control">
									<?php foreach ($types as $value => $text) { ?>
										<option value="<?php echo $value; ?>" <?php if ($value == $block->type_id) echo 'selected="selected"'; ?>><?php echo $text; ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_category; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<select name="category" class="form-control">
									<option value=""></option>
									<?php foreach ($categories as $value => $text) { ?>
										<option value="<?php echo $value; ?>" <?php if ($block->getCategory() && $value == $block->getCategory()->id) echo 'selected="selected"'; ?>><?php echo $text; ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column">
								<?php echo $text_content; ?>
								<?php foreach ($content_buttons as $text => $url) { ?>
									<br /><a href="<?php echo $url; ?>" class="btn btn-primary"><?php echo $text; ?></a>
								<?php } ?>
							</div>
							<div class="col-md-9 col-sm-9 ">
								<textarea class="form-control ckeditor" name="content"><?php echo htmlspecialchars($block->content); ?></textarea>
								<?php echo $form->getHtmlErrorDiv('content'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-12 align-right">
								<a class="btn btn-primary float-left" href="javascript:togglePreview()"><?php echo $text_preview; ?></a>
								<button class="btn btn-primary" type="submit" name="form-block-submit"><?php
									if ($is_add_page) echo $text_add_button;
									else echo $text_update_button;
								?></button>
								<br /><br />
							</div>
						</div>
						<div id="preview" class="row default-padding">
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

	function togglePreview(force_show) {
		if (typeof(force_show) == 'undefined') {
			force_show = false;
		}
		if (!force_show && $('#preview').is(':visible')) {
			$('#preview').hide();
		}
		else {
			$('#preview').show();
		}
	}
	togglePreview(true);

	function updatePreview(content) {
		$('#content-preview').html(content);
		<?php if ($this->config->siteConfig()->enable_latex) { ?>
			MathJax.Hub.Queue(["Typeset", MathJax.Hub, 'content-preview']);
		<?php } ?>
	}
	CKEDITOR.on('instanceCreated', function (e) {
		var editor = e.editor;
		editor.on('change', function (ev) {
			if ($('#preview').is(':visible')) {
				var content = e.editor.getData();
				updatePreview(content);
			}
		});
	});
	CKEDITOR.on('instanceReady', function( ev ) {
		var writer = ev.editor.dataProcessor.writer;
		writer.lineBreakChars = '';
	});
	updatePreview($('textarea[name="content"]').val());
</script>

