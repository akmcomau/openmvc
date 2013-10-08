<div class="container">
	<div class="align-right">
		<form action="" method="post" id="form-category">
			<input type="text" name="name" value="" />
			<button type="submit" class="btn btn-primary" name="form-category-submit"><?php echo $text_add_category; ?></button>
			<?php echo $form->getHtmlErrorDiv('category'); ?>
			<br /><br />
		</form>
	</div>
	<table class="table">
		<tr>
			<th><?php echo $text_name; ?></th>
			<th><?php echo $text_num_children; ?></th>
			<th></th>
		</tr>
		<?php foreach ($categories as $category) { ?>
			<tr>
				<th><?php echo $category['name']; ?></th>
				<th><?php echo $category['children']; ?></th>
				<th>
					<a class="btn btn-primary" href="?category=<?php echo $category['id']; ?>"><?php echo $text_view_children; ?></a>
				</th>
			</tr>
		<?php } ?>
	</table>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>
</script>
