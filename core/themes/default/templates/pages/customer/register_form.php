<form id="form-register" class="form-login-register" action="<?php echo $this->url->getURL('Customer', 'register', [$controller, $method, $params]); ?>" method="post">
<div class="widget">
	<div class="widget-header">
		<h3><?php echo $text_register_header; ?></h3>
	</div>
	<div class="widget-content">
		<input type="text" name="first-name" class="form-control" placeholder="<?php echo $text_first_name; ?>" value="<?php echo $register->getEncodedValue('first-name'); ?>" />
		<?php echo $register->getHtmlErrorDiv('first-name'); ?>
		<hr />
		<input type="text" name="last-name" class="form-control" placeholder="<?php echo $text_last_name; ?>" value="<?php echo $register->getEncodedValue('last-name'); ?>" />
		<?php echo $register->getHtmlErrorDiv('last-name'); ?>
		<hr />
		<input type="text" name="email" class="form-control" placeholder="<?php echo $text_email; ?>" value="<?php echo $register->getEncodedValue('email'); ?>" />
		<?php echo $register->getHtmlErrorDiv('email'); ?>
		<hr />
		<input type="text" name="username" class="form-control" placeholder="<?php echo $text_username; ?>" value="<?php echo $register->getEncodedValue('username'); ?>" />
		<?php echo $register->getHtmlErrorDiv('username'); ?>
		<hr />
		<input type="password" name="password1" class="form-control" placeholder="<?php echo $text_password1; ?>" />
		<?php echo $register->getHtmlErrorDiv('password1'); ?>
		<hr />
		<input type="password" name="password2" class="form-control" placeholder="<?php echo $text_password2; ?>" />
		<?php echo $register->getHtmlErrorDiv('password2'); ?>
		<button type="submit" name="form-register-submit" class="btn btn-lg btn-primary btn-block"><?php echo $text_register_button; ?></button>
	</div>
</div>
</form>
