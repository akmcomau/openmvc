<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form class="admin-form" method="post" id="form-customer">
				<div class="widget">
					<div class="widget-header">
						<h3><?php
							if ($is_add_page) echo $text_add_header;
						 	else echo $text_update_header;
						?></h3>
					</div>
					<div class="widget-content">
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_login; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="login" value="<?php echo htmlspecialchars($customer->login); ?>" />
								<?php echo $form->getHtmlErrorDiv('login'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_email; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="email" value="<?php echo htmlspecialchars($customer->email); ?>" />
								<?php echo $form->getHtmlErrorDiv('email'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_first_name; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($customer->first_name); ?>" />
								<?php echo $form->getHtmlErrorDiv('first_name'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_last_name; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($customer->last_name); ?>" />
								<?php echo $form->getHtmlErrorDiv('last_name'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_phone; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($customer->phone); ?>" />
								<?php echo $form->getHtmlErrorDiv('phone'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_active; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<select class="form-control" name="active">
									<option value="1" <?php if ($customer->active) echo 'selected="selected"'; ?>><?php echo $text_active_yes; ?></option>
									<option value="0" <?php if (!$customer->active) echo 'selected="selected"'; ?>><?php echo $text_active_no; ?></option>
								</select>
								<?php echo $form->getHtmlErrorDiv('active'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_password1; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="password" class="form-control" name="password1" value="" />
								<?php echo $form->getHtmlErrorDiv('password1'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_password2; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="password" class="form-control" name="password2" value="" />
								<?php echo $form->getHtmlErrorDiv('password2'); ?>
							</div>
						</div>
						<?php if (!$is_add_page) { ?>
							<hr class="separator-2column" />
							<?php echo $text_leave_password_blank; ?>
						<?php } ?>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-12 align-center">
								<button class="btn btn-primary" type="submit" name="form-customer-submit"><?php
									if ($is_add_page) echo $text_add_button;
									else echo $text_update_button;
								?></button>
								<br /><br />
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>
</script>

