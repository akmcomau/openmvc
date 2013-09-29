<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form id="form-login" class="form-login-register" action="<?php echo $this->url->getURL('Administrator', 'login'); ?>" method="post">
        		<h2 class="form-login-register-heading"><?php echo $this->language->get('login_header'); ?></h2>
				<?php echo $login->getHtmlErrorDiv('login-failed', 'login-failed'); ?>
				<input type="text" name="username" class="form-control" placeholder="<?php echo $this->language->get('username'); ?>" autofocus="autofocus" value="<?php echo $login->getEncodedValue('username'); ?>" />
				<?php echo $login->getHtmlErrorDiv('username'); ?>
				<hr />
				<input type="password" name="password" class="form-control" placeholder="<?php echo $this->language->get('password'); ?>" />
				<?php echo $login->getHtmlErrorDiv('password'); ?>
				<button name="form-login-submit" class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $this->language->get('login_button'); ?></button>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $login->getJavascriptValidation(); ?>
</script>
