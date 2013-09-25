<div class="container">
	 <?php if ($message && $message_class) { ?>
		<div class="row <?php echo $message_class; ?>">
			<?php echo $message; ?>
		</div>
	<?php } ?>
	<div class="row">
		<div class="col-md-6">
			<form id="form-login" class="form-login-register" action="<?php echo $this->url->getURL('Account', 'login'); ?>" method="post">
        		<h2 class="form-login-register-heading">Please sign in</h2>
				<input type="hidden" name="form-login" value="1"/>
				<input type="text" name="username" class="form-control" placeholder="Username" autofocus="autofocus" />
				<div id="username-error" class="form-error"></div>
				<input type="password" name="password" class="form-control" placeholder="Password" />
				<div id="password-error" class="form-error"></div>
				<label class="checkbox">
					<input type="checkbox" name="remember-me" value="1" /> Remember me
				</label>
				<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
			</form>
		</div>
		<div class="col-md-6">
			<form id="form-register" class="form-login-register" action="<?php echo $this->url->getURL('Account', 'register'); ?>" method="post">
        		<h2 class="form-login-register-heading">Register</h2>
				<input type="text" name="first-name" class="form-control" placeholder="First Name" />
				<div id="first-name-error" class="form-error"></div>
				<hr />
				<input type="text" name="last-name" class="form-control" placeholder="Last Name" />
				<div id="last-name-error" class="form-error"></div>
				<hr />
				<input type="text" name="email" class="form-control" placeholder="Email address" />
				<div id="email-error" class="form-error"></div>
				<hr />
				<input type="text" name="username" class="form-control" placeholder="Username" />
				<div id="username-error" class="form-error"></div>
				<hr />
				<input type="password" name="password1" class="form-control" placeholder="Password" />
				<div id="password1-error" class="form-error"></div>
				<hr />
				<input type="password" name="password2" class="form-control" placeholder="Confirm Password" />
				<div id="password2-error" class="form-error"></div>
				<button type="submit" id="button-register" class="btn btn-lg btn-primary btn-block">Register</button>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	function validateRegisterForm(event) {
		var form = {
			'first-name': {
				type: 'string',
				min_length: 2,
				max_length: 32
			},
			'last-name': {
				type: 'string',
				min_length: 2,
				max_length: 32
			},
			email: {
				type: 'email'
			},
			username: {
				type: 'string',
				min_length: 6,
				max_length: 32,
				message: "Between 6-32 characters"
			},
			password1: {
				type: 'string',
				min_length: 6,
				max_length: 32,
				message: "Between 6-32 characters<br />With at least one number"
			},
			password2: {
				type: 'string',
				min_length: 6,
				max_length: 32,
				message: "Between 6-32 characters<br />With at least one number"
			}
		};

		was_error = false;
		if (!validate_form('form-register', form)) {
			was_error = true;
		}
		if ($('input[name="password1"]').val() != $('input[name="password2"]').val()) {
			display_validation_error('password1', "Passwords do not match");
			was_error = true;
		}
		if (was_error) {
			event.stopPropagation();
			return false;
		}
	}

	$(document).ready(function() {
		$('#button-register').click(function(event) {
			return validateRegisterForm(event);
		});
	});
</script>
