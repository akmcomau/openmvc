<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form class="admin-form" method="post" id="form-page">
				<div class="widget">
					<div class="widget-header">
						<h3><?php
							if ($is_add_page) echo $text_add_header;
						 	else echo $text_update_header;
						?></h3>
					</div>
					<div class="widget-content">
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_url; ?></div>
							<div class="col-md-9 col-sm-9 "><a href="<?php echo $url; ?>"><?php echo $url; ?></a></div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_category; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<select name="category" class="form-control">
									<option value=""></option>
									<?php foreach ($categories as $value => $text) { ?>
										<option value="<?php echo $value; ?>" <?php if ($value == $category) echo 'selected="selected"'; ?>><?php echo $text; ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_controller; ?></div>
							<div class="col-md-9 col-sm-9 "><?php echo $controller; ?></div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_controller_alias; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<?php
								if ($controller == 'Root') {
									?>/<input type="hidden" name="controller_alias" value="<?php echo $controller_alias; ?>" /><?php
								}
								else {
									?><input type="text" class="form-control" name="controller_alias" value="<?php echo $controller_alias; ?>" /><?php
									echo $form->getHtmlErrorDiv('controller_alias');
								}
								?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_method_name; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<?php
								if ($is_add_page && $misc_page) {
									?>
									page/ <input type="text" class="form-control" name="method_name" value="<?php echo $method; ?>" style="max-width: 560px; display: inline-block;" />
									<?php
									echo $form->getHtmlErrorDiv('method_name');
								}
								elseif ($misc_page) {
									echo 'page/'.$method;
								}
								else {
									echo $method;
								}
								?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_method_alias; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="method_alias" value="<?php echo $method_alias; ?>" />
								<?php echo $form->getHtmlErrorDiv('method_alias'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_title; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="meta_title" value="<?php echo htmlspecialchars($meta_tags['title']); ?>" />
								<?php echo $form->getHtmlErrorDiv('meta_title'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_description; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<textarea class="form-control" name="meta_description"><?php echo htmlspecialchars($meta_tags['description']); ?></textarea>
								<?php echo $form->getHtmlErrorDiv('meta_description'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_keywords; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="meta_keywords" value="<?php echo htmlspecialchars($meta_tags['keywords']); ?>" />
								<?php echo $form->getHtmlErrorDiv('meta_keywords'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_link_text; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="link_text" value="<?php echo htmlspecialchars($link_text); ?>" />
								<?php echo $form->getHtmlErrorDiv('link_text'); ?>
							</div>
						</div>
						<?php if ($misc_page) { ?>
							<hr class="separator-2column" />
							<div class="row">
								<div class="col-md-3 col-sm-3 title-2column">
									<?php echo $text_content; ?>
									<br />
								</div>
								<div class="col-md-9 col-sm-9 ">
									<textarea class="form-control ckeditor" name="content"><?php echo htmlspecialchars($content); ?></textarea>
									<?php echo $form->getHtmlErrorDiv('content'); ?>
								</div>
							</div>
						<?php } else { ?>
							<input type="hidden" name="" value="" />
						<?php } ?>
						<hr class="separator-2column" />
						<div class="row">
							<?php if ($misc_page) { ?>
								<div class="col-md-12 align-right">
									<a class="btn btn-primary float-left" href="javascript:togglePreview()"><?php echo $text_preview; ?></a>
									<button class="btn btn-primary" type="submit" name="form-page-submit"><?php
									  if ($is_add_page) echo $text_add_button;
									  else echo $text_update_button;
									?></button>
								</div>
							<?php } else { ?>
								<div class="col-md-12 align-center">
									<button class="btn btn-primary" type="submit" name="form-page-submit"><?php
									  if ($is_add_page) echo $text_add_button;
									  else echo $text_update_button;
									?></button>
								</div>
							<?php } ?>
						</div>
						<?php if ($misc_page) { ?>
							<div id="preview" class="row default-padding">
								<hr class="separator-2column" />
								<h2 class="align-center"><?php echo $text_preview; ?></h2>
								<hr class="separator-2column" />
								<div id="content-preview"></div>
							</div>
						<?php } ?>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>

	var showPreview = true;
	function togglePreview() {
		if ($('#preview').is(':visible')) {
			$('#preview').hide();
		}
		else {
			$('#preview').show();
		}
	}
	togglePreview();

	function updatePreview(content) {
		$('#content-preview').html(content);
		<?php if ($this->config->siteConfig()->enable_latex) { ?>
			MathJax.Hub.Queue(["Typeset", MathJax.Hub, 'content-preview']);
		<?php } ?>
	}

	CKEDITOR.on('instanceCreated', function (e) {
		var editor = e.editor;
		editor.on('change', function (ev) {
			if (showPreview) {
				var content = e.editor.getData();
				updatePreview(content);
			}
		});
	});
	updatePreview($('textarea[name="content"]').val());
</script>
