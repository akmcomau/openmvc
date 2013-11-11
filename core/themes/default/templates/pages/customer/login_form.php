<form id="form-login" class="form-login-register" action="<?php echo $this->url->getURL('Customer', 'login', [$controller, $method, $params]); ?>" method="post">
<div class="widget">
	<div class="widget-header">
		<h3><?php echo $text_login_header; ?></h3>
	</div>
	<div class="widget-content">
		<?php echo $login->getHtmlErrorDiv('login-failed', 'login-failed'); ?>
		<input type="text" name="username" class="form-control" placeholder="<?php echo $text_username; ?>" autofocus="autofocus" value="<?php echo $login->getEncodedValue('username'); ?>" />
		<?php echo $login->getHtmlErrorDiv('username'); ?>
		<hr />
		<input type="password" name="password" class="form-control" placeholder="<?php echo $text_password; ?>" />
		<?php echo $login->getHtmlErrorDiv('password'); ?>
		<button name="form-login-submit" class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $text_login_button; ?></button>
		<br />
		<br />
		<div  class="align-center">
			<a href="<?php echo $this->url->getURL('Customer', 'forgot'); ?>"><?php echo $text_forgot_password; ?></a>
		</div>
	</div>
</div>
</form>
