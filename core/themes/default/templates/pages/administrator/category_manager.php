<?php
function echoCategory($readonly, $allow_subcategories, $add_text, $category, $depth = 0) {
	?>
	<tr>
		<?php if (!$readonly) { ?>
			<td class="select"><input type="checkbox" name="selected[]" value="<?php echo $category['id'];?>" /></td>
		<?php } ?>
		<td class="name">
			<?php if ($allow_subcategories) { ?>
				<a id="subcategories-<?php echo $category['id'];?>" href="javascript:toggleSubcategory(<?php echo $category['id'];?>)"><i class="icon-expand"></i></a>
			<?php } ?>
			<span id="category-name-<?php echo $category['id'];?>"><?php echo $category['name']; ?></span>
			<?php if (!$readonly) { ?>
				<a id="edit-category-<?php echo $category['id'];?>" href="javascript:editCategoryName(<?php echo $category['id'];?>);"><i class="icon-edit"></i></a>
			<?php } ?>
		</td>
		<?php if ($allow_subcategories) { ?>
			<td class="subcategories"><?php echo isset($category['num_subcategories']) ? $category['num_subcategories'] : 0; ?></td>
		<?php } ?>
	</tr>
	<tr id="subcategory-<?php echo $category['id'];?>" class="subcategory">
		<td></td>
		<td colspan="2">
			<table class="table">
				<?php
				if (isset($category['subcategories'])) {
					foreach ($category['subcategories'] as $sub_category) {
						echoCategory($readonly, $allow_subcategories, $add_text, $sub_category, ++$depth);
					}
				}
				?>
				<?php if (!$readonly) { ?>
					<tr>
						<td></td>
						<td id="add-subcategory-<?php echo $category['id'];?>"><a class="" href="javascript:addSubcategory(<?php echo $category['id'];?>);"><?php echo $add_text; ?></a></td>
						<td></td>
					</tr>
				<?php } ?>
			</table>
		</td>
	</tr>
	<?php
}
?>
<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form class="admin-search-form" method="get" id="form-page-search">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $title; ?></h3>
					</div>
					<div class="widget-content">
						<div class="align-right">
							<?php if (!$readonly) { ?>
							<form action="" method="post" id="form-category">
								<input type="hidden" name="add_category" value="1" />
								<input type="hidden" name="category" value="" />
								<input type="text" name="name" value="" />
								<button type="submit" class="btn btn-primary" name="form-category-submit"><?php echo $text_add_category; ?></button>
								<br /><br />
							</form>
							<?php } ?>
						</div>
						<form action="" method="post" id="form-category-list">
							<table class="table category-manager">
								<tr>
									<?php if (!$readonly) { ?>
									<th>&nbsp;</th>
									<?php } ?>
									<th><?php echo $text_name; ?></th>
									<?php if ($allow_subcategories) { ?>
									<th><?php echo $text_num_subcategories; ?></th>
									<?php } ?>
								</tr>
								<?php foreach ($categories as $category) {
								  echoCategory($readonly, $allow_subcategories, $text_add_subcategory, $category);
								} ?>
							</table>
							<button type="submit" class="btn btn-primary" name="form-category-list-submit" onclick="return deleteSelected();"><?php echo $text_delete_selected; ?></button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<form action="" method="post" id="form-subcategory-add" class="hidden">
	<input type="hidden" name="add_subcategory" value="1" />
	<input type="hidden" name="category" value="" />
	<input type="text" class="form-control" name="name" value="" />
</form>
<form action="" method="post" id="form-category-edit" class="hidden">
	<input type="hidden" name="edit_category" value="1" />
	<input type="hidden" name="category" value="" />
	<input type="text" class="form-control" name="name" value="" />
</form>
<br />
<script type="text/javascript">
	function toggleSubcategory(id) {
		$('#subcategories-'+id).removeClass('subcategory');
		if ($('#subcategory-'+id).is(':visible')) {
			$('#subcategories-'+id).html('<i class="icon-expand"></i>');
			$('#subcategory-'+id).hide();
		}
		else {
			$('#subcategories-'+id).html('<i class="icon-collapse"></i>');
			$('#subcategory-'+id).show();
		}
	}

	function addSubcategory(id) {
		var form = $('#form-subcategory-add').clone();
		form.removeClass('hidden');
		form.find('input[name="category"]').val(id);
		form.attr('id', 'form-subcategory-add-'+id);

		$('#add-subcategory-'+id).html('')
		$('#add-subcategory-'+id).append(form);
	}

	function editCategoryName(id) {
		var name = $('#category-name-'+id).html();
		var form = $('#form-category-edit').clone();
		form.removeClass('hidden');
		form.find('input[name="name"]').val(name);
		form.find('input[name="category"]').val(id);
		form.attr('id', 'form-category-edit-'+id);

		$('#category-name-'+id).parent().find('a').hide();
		$('#category-name-'+id).html('')
		$('#category-name-'+id).append(form);
	}

	function deleteSelected() {
		return confirm('<?php echo $text_confirm_delete; ?>');
	}

	<?php if ($allow_subcategories) { ?>
		<?php foreach ($open_categories as $category) { ?>
			toggleSubcategory(<?php echo $category; ?>);
		<?php } ?>
	<?php } ?>
</script>
