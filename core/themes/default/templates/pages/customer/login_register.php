<div class="container">
	<div class="row">
		<div class="col-md-6 col-sm-6">
			<?php require('login_form.php'); ?>
		</div>
		<div class="col-md-6 col-sm-6">
			<?php require('register_form.php'); ?>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $login->getJavascriptValidation(); ?>
	<?php echo $register->getJavascriptValidation(); ?>
</script>
