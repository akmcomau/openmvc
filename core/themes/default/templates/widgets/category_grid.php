<div class="row">
	<?php foreach($categories as $category) { ?>
		<div class="col-md-3 col-sm-4 col-xs-6 <?php echo $category->hasImage() ? 'category-cell-image' : 'category-cell'; ?>">
			<a href="<?php echo $this->url->getUrl('Products', 'index', ['category', $category->id, $this->url->canonical($category->name)]); ?>" class="category">
				<h4><?php echo htmlspecialchars($category->name); ?></h4>
				<?php
					$image = $category->getImageThumbnailUrl();
					if ($image) {
						?><img src="<?php echo $image; ?>" /><?php
					}
					else {
						?><img src="<?php echo $this->url->getStaticUrl('/core/themes/default/images/no_image.svg'); ?>" /><?php
					}
				?>
			</a>
		</div>
	<?php } ?>
</div>
