<?php
function echoCategory($add_text, $category, $depth = 0) {
	?>
	<tr>
		<td class="select"><input type="checkbox" name="selected[]" value="<?php echo $category['id'];?>" /></td>
		<td class="name">
			<a id="subcategories-<?php echo $category['id'];?>" href="javascript:toggleSubcategory(<?php echo $category['id'];?>)"><i class="icon-expand"></i></a>
			<span id="category-name-<?php echo $category['id'];?>"><?php echo $category['name']; ?></span>
			<a id="edit-category-<?php echo $category['id'];?>" href="javascript:editCategoryName(<?php echo $category['id'];?>);"><i class="icon-edit"></i></a>
		</td>
		<td class="subcategories"><?php echo isset($category['num_subcategories']) ? $category['num_subcategories'] : 0; ?></td>
	</tr>
	<tr id="subcategory-<?php echo $category['id'];?>" class="subcategory">
		<td></td>
		<td colspan="2">
			<table class="table">
				<?php
				if (isset($category['subcategories'])) {
					foreach ($category['subcategories'] as $sub_category) {
						echoCategory($add_text, $sub_category, ++$depth);
					}
				}
				?>
				<tr>
					<td></td>
					<td id="add-subcategory-<?php echo $category['id'];?>"><a class="" href="javascript:addSubcategory(<?php echo $category['id'];?>);"><?php echo $add_text; ?></a></td>
					<td></td>
				</tr>
			</table>
		</td>
	</tr>
	<?php
}
?>
<div class="container">
	<div class="align-right">
		<form action="" method="post" id="form-category">
			<input type="hidden" name="add_category" value="1" />
			<input type="hidden" name="category" value="" />
			<input type="text" name="name" value="" />
			<button type="submit" class="btn btn-primary" name="form-category-submit"><?php echo $text_add_category; ?></button>
			<br /><br />
		</form>
	</div>
	<form action="" method="post" id="form-category-list">
		<table class="table category-manager">
			<tr>
				<th>&nbsp;</th>
				<th><?php echo $text_name; ?></th>
				<th><?php echo $text_num_subcategories; ?></th>
			</tr>
			<?php foreach ($categories as $category) {
				echoCategory($text_add_subcategory, $category);
			} ?>
		</table>
		<button type="submit" class="btn btn-primary" name="form-category-list-submit" onclick="return deleteSelected();"><?php echo $text_delete_selected; ?></button>
	</form>
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

	<?php foreach ($open_categories as $category) { ?>
		toggleSubcategory(<?php echo $category; ?>);
	<?php } ?>
</script>
