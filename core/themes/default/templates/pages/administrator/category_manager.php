<?php
// recursive function to render out the categories
function echoCategory($readonly, $has_image, $allow_subcategories, $add_text, $category, $depth = 0) {
	global $static_prefix;
	?>
	<tr>
		<?php if (!$readonly) { ?>
			<td class="select"><input type="checkbox" name="selected[]" value="<?php echo $category['id'];?>" /></td>
		<?php } ?>
		<?php if ($has_image) { ?>
			<td>
				<?php
					if ($category['image']) {
						?><img src="<?php echo $category['thumbnail']; ?>" height="50px" /><?php
					}
					else {
						?><img src="<?php echo $static_prefix; ?>/core/themes/default/images/no_image.svg" height="50px" /><?php
				} ?>
			</td>
		<?php } ?>
		<td class="name">
			<?php if ($allow_subcategories) { ?>
				<a id="subcategories-<?php echo $category['id'];?>" href="javascript:toggleSubcategory(<?php echo $category['id'];?>)"><i class="fa fa-expand"></i></a>
			<?php } ?>
			<span id="category-name-<?php echo $category['id'];?>"><?php echo $category['name']; ?></span>
			<?php if (!$readonly) { ?>
				<a id="edit-category-<?php echo $category['id'];?>" href="javascript:editCategoryName(<?php echo $category['id'];?>);"><i class="fa fa-edit"></i></a>
				&nbsp;
				<a id="image-category-<?php echo $category['id'];?>" href="javascript:uploadCategoryImage(<?php echo $category['id'];?>);"><i class="fa fa-camera"></i></a>
			<?php } ?>
		</td>
		<?php if ($allow_subcategories) { ?>
			<td class="subcategories"><?php echo isset($category['num_subcategories']) ? $category['num_subcategories'] : 0; ?></td>
		<?php } ?>
	</tr>
	<?php if ($allow_subcategories) { ?>
		<tr id="subcategory-<?php echo $category['id'];?>" class="subcategory">
			<td></td>
			<td colspan="3">
				<table class="table">
					<?php
					  if (isset($category['subcategories'])) {
						  foreach ($category['subcategories'] as $sub_category) {
							  echoCategory($readonly, $has_image, $allow_subcategories, $add_text, $sub_category, ++$depth);
						  }
					  }
					?>
					<?php if (!$readonly) { ?>
					<tr>
						<td></td>
						<td colspan="2" id="add-subcategory-<?php echo $category['id'];?>"><a href="javascript:addSubcategory(<?php echo $category['id'];?>);"><?php echo $add_text; ?></a></td>
						<td></td>
					</tr>
					<?php } ?>
				</table>
			</td>
		</tr>
	<?php
	}
}
?>
<div class="container">
	<div class="row">
		<div class="col-md-12">
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
					<form action="<?php echo $this->url->getUrl($controller_name, $method_name); ?>" method="post" id="form-category-list">
						<table class="table category-manager">
							<tr>
								<?php if (!$readonly) { ?>
								<th>&nbsp;</th>
								<?php } ?>
								<?php if ($has_image) { ?>
								<th>&nbsp;</th>
								<?php } ?>
								<th><?php echo $text_name; ?></th>
								<?php if ($allow_subcategories) { ?>
								<th><?php echo $text_num_subcategories; ?></th>
								<?php } ?>
							</tr>
							<?php foreach ($categories as $category) {
							  echoCategory($readonly, $has_image, $allow_subcategories, $text_add_subcategory, $category);
							} ?>
						</table>
						<button type="submit" class="btn btn-primary" name="form-category-list-submit" onclick="return deleteSelected();"><?php echo $text_delete_selected; ?></button>
					</form>
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
<form action="" method="post" id="form-category-image" class="hidden" enctype="multipart/form-data">
	<input type="hidden" name="image_category" value="1" />
	<input type="hidden" name="category" value="" />
	<input type="file" name="image" />
	<input type="submit" class="btn btn-primary btn-small" value="<?php echo $text_upload_image; ?>" />
</form>
<br />
<script type="text/javascript">
	<?php echo $message_js; ?>

	function toggleSubcategory(id) {
		$('#subcategories-'+id).removeClass('subcategory');
		if ($('#subcategory-'+id).is(':visible')) {
			$('#subcategories-'+id).html('<i class="fa fa-expand"></i>');
			$('#subcategory-'+id).hide();
		}
		else {
			$('#subcategories-'+id).html('<i class="fa fa-collapse"></i>');
			$('#subcategory-'+id).show();
		}
	}

	function uploadCategoryImage (id) {
		var form = $('#form-category-image').clone();
		form.removeClass('hidden');
		form.find('input[name="category"]').val(id);
		form.attr('id', 'form-subcategory-image-'+id);

		$('#category-name-'+id).parent().find('a').hide();
		$('#category-name-'+id).html('')
		$('#category-name-'+id).append(form);
	}

	function addSubcategory(id) {
		var form = $('#form-subcategory-add').clone();
		form.removeClass('hidden');
		form.find('input[name="category"]').val(id);
		form.attr('id', 'form-subcategory-add-'+id);
		form.find('input[name="name"]').blur(function() {
			$(this).parent().submit();
		});

		$('#add-subcategory-'+id).html('')
		$('#add-subcategory-'+id).append(form);

		$('#form-subcategory-add-'+id+' input[name="name"]').focus();
	}

	function editCategoryName(id) {
		var name = $('#category-name-'+id).html();
		var form = $('#form-category-edit').clone();
		form.removeClass('hidden');
		form.find('input[name="name"]').val(name);
		form.find('input[name="category"]').val(id);
		form.attr('id', 'form-category-edit-'+id);
		form.find('input[name="name"]').blur(function() {
			$(this).parent().submit();
		});

		$('#category-name-'+id).parent().find('a').hide();
		$('#category-name-'+id).html('')
		$('#category-name-'+id).append(form);

		$('#form-category-edit-'+id+' input[name="name"]').focus();
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
