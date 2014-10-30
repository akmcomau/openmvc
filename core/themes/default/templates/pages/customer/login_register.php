<div class="inner-page br-black white">
	<div class="heading">
		<h2>Login / Register</h2>
		<p><strong>Neque porro</strong> quisquam est qui <strong>dolorem ipsum quia</strong>, consectetur, velit..."There <strong>dolor sit amet</strong> is no one whter it <strong>adipisci</strong> and wants to have it.</p>
	</div>
	<div class="inner-login">
		<div class="row">
			<div class="sign-up black">
				<div class="col-md-6 col-sm-6">
					<?php $this->includeTemplate('pages/customer/login_form.php'); ?>
				</div>
				<div class="col-md-6 col-sm-6">
					<?php $this->includeTemplate('pages/customer/register_form.php'); ?>
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $register->getJavascriptValidation(); ?>
</script>
