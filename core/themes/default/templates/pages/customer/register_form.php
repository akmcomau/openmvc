<form id="form-register" class="form-login-register" action="<?php echo $this->url->getURL('Customer', 'register', [$controller, $method, $params]); ?>" method="post">
<div class="widget">
	<div class="widget-header">
		<h3><?php echo $this->language->get('register_header'); ?></h3>
	</div>
	<div class="widget-content">
		<input type="text" name="first-name" class="form-control" placeholder="<?php echo $this->language->get('first_name'); ?>" value="<?php echo $register->getEncodedValue('first-name'); ?>" />
		<?php echo $register->getHtmlErrorDiv('first-name'); ?>
		<hr />
		<input type="text" name="last-name" class="form-control" placeholder="<?php echo $this->language->get('last_name'); ?>" value="<?php echo $register->getEncodedValue('last-name'); ?>" />
		<?php echo $register->getHtmlErrorDiv('last-name'); ?>
		<hr />
		<input type="text" name="email" class="form-control" placeholder="<?php echo $this->language->get('email'); ?>" value="<?php echo $register->getEncodedValue('email'); ?>" />
		<?php echo $register->getHtmlErrorDiv('email'); ?>
		<hr />
		<input type="text" name="username" class="form-control" placeholder="<?php echo $this->language->get('username'); ?>" value="<?php echo $register->getEncodedValue('username'); ?>" />
		<?php echo $register->getHtmlErrorDiv('username'); ?>
		<hr />
		<input type="password" name="password1" class="form-control" placeholder="<?php echo $this->language->get('password1'); ?>" />
		<?php echo $register->getHtmlErrorDiv('password1'); ?>
		<hr />
		<input type="password" name="password2" class="form-control" placeholder="<?php echo $this->language->get('password2'); ?>" />
		<?php echo $register->getHtmlErrorDiv('password2'); ?>
		<button type="submit" name="form-register-submit" class="btn btn-lg btn-primary btn-block"><?php echo $this->language->get('register_button'); ?></button>
	</div>
</div>
</form>
