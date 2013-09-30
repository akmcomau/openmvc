<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form id="form-login" class="form-login-register" action="<?php echo $this->url->getURL('Customer', 'login'); ?>" method="post">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $this->language->get('login_header'); ?></h3>
					</div>
					<div class="widget-content">
						<?php echo $login->getHtmlErrorDiv('login-failed', 'login-failed'); ?>
						<input type="text" name="username" class="form-control" placeholder="<?php echo $this->language->get('username'); ?>" autofocus="autofocus" value="<?php echo $login->getEncodedValue('username'); ?>" />
						<?php echo $login->getHtmlErrorDiv('username'); ?>
						<hr />
						<input type="password" name="password" class="form-control" placeholder="<?php echo $this->language->get('password'); ?>" />
						<?php echo $login->getHtmlErrorDiv('password'); ?>
						<button name="form-login-submit" class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $this->language->get('login_button'); ?></button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $login->getJavascriptValidation(); ?>
</script>
