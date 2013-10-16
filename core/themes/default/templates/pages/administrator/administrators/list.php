<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form class="admin-search-form" method="get" id="form-administrator-search">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $text_search; ?></h3>
					</div>
					<div class="widget-content">
						<div class="row">
							<div class="col-md-6">
								<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_email; ?></div>
								<div class="col-md-9 col-sm-9 ">
									<input type="text" class="form-control" name="search_email" value="<?php echo htmlspecialchars($form->getValue('search_email')); ?>" />
									<?php echo $form->getHtmlErrorDiv('search_email'); ?>
								</div>
							</div>
							<div class="col-md-6 visible-xs">
								<hr class="separator-2column" />
							</div>
							<div class="col-md-6">
								<div class="col-md-3 col-sm-3 title-2column">
									<div class="spacer-2column visible-sm"></div>
									<?php echo $text_login; ?>
								</div>
								<div class="col-md-9 col-sm-9 ">
									<div class="spacer-2column visible-sm"></div>
									<input type="text" class="form-control" name="search_login" value="<?php echo htmlspecialchars($form->getValue('search_login')); ?>" />
									<?php echo $form->getHtmlErrorDiv('search_login'); ?>
								</div>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="align-right">
							<button type="submit" class="btn btn-primary" name="form-administrator-search-submit"><?php echo $text_search; ?></button>
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
							<th><?php echo $text_login; ?> <?php echo $pagination->getSortUrls('login'); ?></th>
							<th><?php echo $text_email; ?> <?php echo $pagination->getSortUrls('email'); ?></th>
							<th><?php echo $text_first_name; ?> <?php echo $pagination->getSortUrls('first_name'); ?></th>
							<th><?php echo $text_last_name; ?> <?php echo $pagination->getSortUrls('last_name'); ?></th>
							<th><?php echo $text_active; ?> <?php echo $pagination->getSortUrls('active'); ?></th>
							<th></th>
						</tr>
						<?php foreach ($administrators as $administrator) { ?>
						<tr>
							<td><?php echo $administrator->login; ?></td>
							<td><?php echo $administrator->email; ?></td>
							<td><?php echo $administrator->first_name; ?></td>
							<td><?php echo $administrator->last_name; ?></td>
							<td><?php echo $administrator->active ? $text_active : $text_not_active; ?></td>
							<td>
								<a href="<?php echo $this->url->getURL('administrator/Administrators', 'edit', [$administrator->id]); ?>" class="btn btn-primary"><i class="icon-edit-sign" title="<?php echo $text_edit; ?>"></i></a>
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
