<div class="row">
	<?php foreach($categories as $category) { ?>
		<div class="col-md-3 col-sm-4 col-xs-6 category-cell-image">
			<a href="<?php echo $this->url->getUrl('Products', 'index', ['category', $category->id, $category->name]); ?>" class="category">
				<h4><?php echo htmlspecialchars($category->name); ?></h4>
				<?php
					$image = $category->getImage();
					if ($image) {
						?><img src="<?php echo $image->getThumbnailUrl(); ?>" /><?php
					}
					else {
						?><img src="<?php echo $static_prefix; ?>/core/themes/default/images/no_image.svg" /><?php
					}
				?>
			</a>
		</div>
	<?php } ?>
</div>
