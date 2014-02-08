<div class="<?php echo $page_div_class; ?>">
	<div class="row">
		<div class="col-md-12">
			<?php require('login_form.php'); ?>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $login->getJavascriptValidation(); ?>
</script>
