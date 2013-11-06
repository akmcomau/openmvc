<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form id="form-update-details" action="<?php echo $this->url->getURL('Customer', 'contact_details'); ?>" method="post">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $text_update_details_header; ?></h3>
					</div>
					<div class="widget-content">
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_username; ?></div>
							<div class="col-md-9 col-sm-9 "><?php echo htmlspecialchars($customer->login); ?></div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_first_name; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($customer->first_name); ?>" />
								<?php echo $form->getHtmlErrorDiv('first_name'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_last_name; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($customer->last_name); ?>" />
								<?php echo $form->getHtmlErrorDiv('last_name'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_email; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" name="email" class="form-control" value="<?php echo htmlspecialchars($customer->email); ?>" />
								<?php echo $form->getHtmlErrorDiv('email'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_phone; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer->phone); ?>" />
								<?php echo $form->getHtmlErrorDiv('phone'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-12 align-center">
								<button type="submit" name="form-update-details-submit" class="btn btn-lg btn-primary"><?php echo $text_update_details_button; ?></button>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>
	<?php echo $message_js; ?>
</script>
