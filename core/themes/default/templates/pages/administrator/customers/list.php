<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form action="<?php echo $this->url->getUrl('administrator/Customers', 'index'); ?>" class="admin-search-form" method="get" id="form-customer-search">
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
							<button type="submit" class="btn btn-primary" name="form-customer-search-submit"><?php echo $text_search; ?></button>
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
					<form action="<?php echo $this->url->getUrl('administrator/Customers', 'delete'); ?>" method="post">
						<table class="table">
							<tr>
								<th></th>
								<th nowrap="nowrap"><?php echo $text_login; ?> <?php echo $pagination->getSortUrls('login'); ?></th>
								<th nowrap="nowrap"><?php echo $text_email; ?> <?php echo $pagination->getSortUrls('email'); ?></th>
								<th nowrap="nowrap"><?php echo $text_first_name; ?> <?php echo $pagination->getSortUrls('first_name'); ?></th>
								<th nowrap="nowrap"><?php echo $text_last_name; ?> <?php echo $pagination->getSortUrls('last_name'); ?></th>
								<th nowrap="nowrap"><?php echo $text_active; ?> <?php echo $pagination->getSortUrls('active'); ?></th>
								<th></th>
							</tr>
							<?php foreach ($customers as $customer) { ?>
							<tr>
								<td class="select"><input type="checkbox" name="selected[]" value="<?php echo $customer->id; ?>"" /></td>
								<td><?php echo $customer->login; ?></td>
								<td><?php echo $customer->email; ?></td>
								<td><?php echo $customer->first_name; ?></td>
								<td><?php echo $customer->last_name; ?></td>
								<td><?php echo $customer->active ? $text_active : $text_not_active; ?></td>
								<td>
									<a href="<?php echo $this->url->getUrl('administrator/Customers', 'edit', [$customer->id]); ?>" class="btn btn-primary"><i class="fa fa-edit" title="<?php echo $text_edit; ?>"></i></a>
								</td>
							</tr>
							<?php } ?>
						</table>
						<button type="submit" class="btn btn-primary" name="form-customer-list-submit" onclick="return deleteSelected();"><?php echo $text_delete_selected; ?></button>
					</form>
					<div class="pagination">
						<?php echo $pagination->getPageLinks(); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>
	<?php echo $message_js; ?>

	function deleteSelected() {
		return confirm('<?php echo $text_confirm_delete; ?>');
	}
</script>
