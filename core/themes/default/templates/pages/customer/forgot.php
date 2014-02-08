<div class="<?php echo $page_div_class; ?>">
	<br />
	<div class="row">
		<div class="col-md-12">
			<form id="form-register" action="<?php echo $this->url->getUrl('Customer', 'forgot'); ?>" method="post">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $text_forgot_password; ?></h3>
					</div>
					<div class="widget-content">
						<hr />
						<input type="text" name="email" class="form-control" placeholder="<?php echo $text_email; ?>" value="" />
						<hr />
						<div>
							<button type="submit" name="forgot-username" class="btn btn-lg btn-primary" value="1"><?php echo $text_button_username; ?></button>
							<button type="submit" name="forgot-password" class="btn btn-lg btn-primary float-right" value="1"><?php echo $text_button_password; ?></button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $message_js; ?>
</script>
