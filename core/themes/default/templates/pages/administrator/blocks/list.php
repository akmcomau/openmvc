<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form class="admin-search-form" method="get" id="form-block-search">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $text_search; ?></h3>
					</div>
					<div class="widget-content">
						<div class="row">
							<div class="col-md-6">
								<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_title; ?></div>
								<div class="col-md-9 col-sm-9 ">
									<input type="text" class="form-control" name="search_title" value="<?php echo htmlspecialchars($form->getValue('search_title')); ?>" />
									<?php echo $form->getHtmlErrorDiv('search_title'); ?>
								</div>
							</div>
							<div class="col-md-6 visible-xs">
								<hr class="separator-2column" />
							</div>
							<div class="col-md-6">
								<div class="col-md-3 col-sm-3 title-2column">
									<div class="spacer-2column visible-sm"></div>
									<?php echo $text_tag; ?>
								</div>
								<div class="col-md-9 col-sm-9 ">
									<div class="spacer-2column visible-sm"></div>
									<input type="text" class="form-control" name="search_tag" value="<?php echo htmlspecialchars($form->getValue('search_tag')); ?>" />
									<?php echo $form->getHtmlErrorDiv('search_tag'); ?>
								</div>
							</div>
						</div>
						<hr class="separator-2column visible-md visible-lg" />
						<div class="spacer-2column visible-sm"></div>
						<div class="row">
							<div class="col-md-6">
								<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_category; ?></div>
								<div class="col-md-9 col-sm-9 ">
									<select class="form-control" name="search_category">
										<option value=""></option>
										<?php foreach ($categories as $value => $text) { ?>
											<option value="<?php echo $value; ?>" <?php if ($form->getValue('search_category') == $value) echo 'selected="selected"'; ?>><?php echo $text; ?></option>
										<?php } ?>
									</select>
									<?php echo $form->getHtmlErrorDiv('search_category'); ?>
								</div>
							</div>
							<div class="col-md-6 visible-xs">
								<hr class="separator-2column visible-xs" />
							</div>
							<div class="col-md-6">
								<div class="spacer-2column visible-sm"></div>
								<div class="col-md-3 col-sm-3 title-2column">
									<div class="spacer-2column visible-sm"></div>
									<?php echo $text_type; ?>
								</div>
								<div class="col-md-9 col-sm-9 ">
									<div class="spacer-2column visible-sm"></div>
									<select class="form-control" name="search_type">
									<?php echo $form->getHtmlErrorDiv('search_type'); ?>
									</select>
								</div>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="align-right">
							<button type="submit" class="btn btn-primary" name="form-block-search-submit"><?php echo $text_search; ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<div class="widget">
				<div class="widget-header">
					<h3><?php echo $text_search_results; ?></h3>
				</div>
				<div class="widget-content">
					<div class="pagination">
						<?php echo $pagination->getPageLinks(); ?>
					</div>
					<table class="table">
						<tr>
							<th><?php echo $text_title; ?> <?php echo $pagination->getSortUrls('title'); ?></th>
							<th><?php echo $text_tag; ?> <?php echo $pagination->getSortUrls('tag'); ?></th>
							<th><?php echo $text_category; ?> <?php echo $pagination->getSortUrls('category_name'); ?></th>
							<th></th>
						</tr>
						<?php foreach ($blocks as $block) { ?>
						<tr>
							<td><?php echo $block->title; ?></td>
							<td><?php echo $block->tag; ?></td>
							<td><?php echo 'categories'; ?></td>
							<td>
								<a href="<?php echo $this->url->getURL('administrator/Blocks', 'edit', [$block->tag]); ?>" class="btn btn-primary"><i class="icon-edit-sign" title="<?php echo $text_edit; ?>"></i></a>
							</td>
						</tr>
						<?php } ?>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>
</script>
