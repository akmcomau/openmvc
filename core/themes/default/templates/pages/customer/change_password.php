<div class="<?php echo $page_class; ?>">
	<div class="row">
		<div class="col-md-12">
			<form id="form-change-password" class="form-login-register" action="<?php echo $this->url->getUrl('Customer', 'change_password'); ?>" method="post">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $text_change_password_header; ?></h3>
					</div>
					<div class="widget-content">
						<?php if (!$force_password_change) { ?>
							<input type="password" name="current_password" class="form-control" placeholder="<?php echo $text_current_password; ?>" autofocus="autofocus" value="" />
							<?php echo $form->getHtmlErrorDiv('current_password'); ?>
							<hr />
						<?php } ?>
						<input type="password" name="password1" class="form-control" placeholder="<?php echo $text_new_password; ?>" autofocus="autofocus" value="" />
						<?php echo $form->getHtmlErrorDiv('password1'); ?>
						<hr />
						<input type="password" name="password2" class="form-control" placeholder="<?php echo $text_password2; ?>" />
						<?php echo $form->getHtmlErrorDiv('password2'); ?>
						<button name="form-change-password-submit" class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $text_change_password_button; ?></button>
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
