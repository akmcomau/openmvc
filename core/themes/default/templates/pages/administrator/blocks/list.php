<div class="container">
	<div class="pagination">
		<?php echo $pagination->getPageLinks(); ?>
	</div>

	<table class="table">
		<tr>
			<th><?php echo $text_title; ?> <?php echo $pagination->getSortUrls('title'); ?></th>
			<th><?php echo $text_tag; ?> <?php echo $pagination->getSortUrls('tag'); ?></th>
			<th><?php echo $text_category; ?> <?php echo $pagination->getSortUrls('category_name'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($blocks as $block) { ?>
			<tr>
				<td><?php echo $block->title; ?></td>
				<td><?php echo $block->tag; ?></td>
				<td><?php echo 'categories'; ?></td>
				<td>
					<a href="<?php echo $this->url->getURL('administrator/Blocks', 'edit', [$block->tag]); ?>" class="btn btn-primary"><i class="icon-edit-sign" title="<?php echo $text_edit; ?>"></i></a>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>
