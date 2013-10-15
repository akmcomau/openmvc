<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form class="admin-search-form" method="get" id="form-page-search">
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
									<?php echo $text_url; ?>
								</div>
								<div class="col-md-9 col-sm-9 ">
									<div class="spacer-2column visible-sm"></div>
									<input type="text" class="form-control" name="search_url" value="<?php echo htmlspecialchars($form->getValue('search_url')); ?>" />
									<?php echo $form->getHtmlErrorDiv('search_url'); ?>
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
											<option value="<?php echo $value; ?>" <?php if ($value == $form->getValue('search_category')) echo 'selected="selected"'; ?>><?php echo $text; ?></option>
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
									<?php echo $text_editable; ?>
								</div>
								<div class="col-md-9 col-sm-9 ">
									<div class="spacer-2column visible-sm"></div>
									<div class="col-md-3 col-sm-3 ">
										<label>
											<input type="radio" name="search_editable" value="all" <?php if ($form->getValue('search_editable') == 'all') echo 'checked="checked"'; ?> />
											<?php echo $text_editable_all; ?>
										</label>
									</div>
									<div class="col-md-5 col-sm-5 ">
										<label>
											<input type="radio" name="search_editable" value="editable" <?php if (is_null($form->getValue('search_editable')) || $form->getValue('search_editable') == 'editable') echo 'checked="checked"'; ?> />
											<?php echo $text_editable_yes; ?>
										</label>
									</div>
									<div class="col-md-4 col-sm-4 ">
										<label>
											<input type="radio" name="search_editable" value="fixed" <?php if ($form->getValue('search_editable') == 'fixed') echo 'checked="checked"'; ?> />
											<?php echo $text_editable_no; ?>
										</label>
									</div>
									<?php echo $form->getHtmlErrorDiv('search_editable'); ?>
								</div>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="align-right">
							<button type="submit" class="btn btn-primary" name="form-page-search-submit"><?php echo $text_search; ?></button>
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
							<th><?php echo $text_url; ?> <?php echo $pagination->getSortUrls('url'); ?></th>
							<th><?php echo $text_title; ?> <?php echo $pagination->getSortUrls('title'); ?></th>
							<th><?php echo $text_permissions; ?> <?php echo $pagination->getSortUrls('permissions'); ?></th>
							<th class="align-center"><?php echo $text_description; ?> <?php echo $pagination->getSortUrls('description'); ?></th>
							<th class="align-center"><?php echo $text_keywords; ?> <?php echo $pagination->getSortUrls('keywords'); ?></th>
							<th></th>
						</tr>
						<?php foreach ($pages as $controller => $method) { ?>
						<tr>
							<td><?php echo $method['url']; ?></td>
							<td><?php echo $method['title']; ?></td>
							<td><?php echo $method['permissions']; ?></td>
							<td class="align-center">
								<?php if ($method['description']) { ?>
								<i class="icon-thumbs-up"></i>
								<?php } else { ?>
								<i class="icon-thumbs-down"></i>
								<?php } ?>
							</td>
							<td class="align-center">
								<?php if ($method['keywords']) { ?>
								<i class="icon-thumbs-up"></i>
								<?php } else { ?>
								<i class="icon-thumbs-down"></i>
								<?php } ?>
							</td>
							<td>
								<a href="<?php echo $this->url->getURL('administrator/Pages', 'edit', [$method['controller'], $method['main_method'], $method['sub_method']]); ?>" class="btn btn-primary"><i class="icon-edit-sign" title="<?php echo $text_edit; ?>"></i></a>
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
