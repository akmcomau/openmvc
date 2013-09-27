<div class="container">
	<div class="row">
		<div class="col-md-6">
			<form id="form-login" class="form-login-register" action="<?php echo $this->url->getURL('Account', 'login'); ?>" method="post">
        		<h2 class="form-login-register-heading">Please sign in</h2>
				<?php echo $login->getHtmlErrorDiv('login-failed', 'login-failed'); ?>
				<input type="text" name="username" class="form-control" placeholder="Username" autofocus="autofocus" value="<?php echo $login->getEncodedValue('username'); ?>" />
				<?php echo $login->getHtmlErrorDiv('username'); ?>
				<hr />
				<input type="password" name="password" class="form-control" placeholder="Password" />
				<?php echo $login->getHtmlErrorDiv('password'); ?>
				<button name="form-login-submit" class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
			</form>
		</div>
		<div class="col-md-6">
			<form id="form-register" class="form-login-register" action="<?php echo $this->url->getURL('Account', 'register'); ?>" method="post">
        		<h2 class="form-login-register-heading">Register</h2>
				<input type="text" name="first-name" class="form-control" placeholder="First Name" value="<?php echo $register->getEncodedValue('first-name'); ?>" />
				<?php echo $register->getHtmlErrorDiv('first-name'); ?>
				<hr />
				<input type="text" name="last-name" class="form-control" placeholder="Last Name" value="<?php echo $register->getEncodedValue('last-name'); ?>" />
				<?php echo $register->getHtmlErrorDiv('last-name'); ?>
				<hr />
				<input type="text" name="email" class="form-control" placeholder="Email address" value="<?php echo $register->getEncodedValue('email'); ?>" />
				<?php echo $register->getHtmlErrorDiv('email'); ?>
				<hr />
				<input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo $register->getEncodedValue('username'); ?>" />
				<?php echo $register->getHtmlErrorDiv('username'); ?>
				<hr />
				<input type="password" name="password1" class="form-control" placeholder="Password" />
				<?php echo $register->getHtmlErrorDiv('password1'); ?>
				<hr />
				<input type="password" name="password2" class="form-control" placeholder="Confirm Password" />
				<?php echo $register->getHtmlErrorDiv('password2'); ?>
				<button type="submit" name="form-register-submit" class="btn btn-lg btn-primary btn-block">Register</button>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $login->getJavascriptValidation(); ?>
	<?php echo $register->getJavascriptValidation(); ?>
</script>
