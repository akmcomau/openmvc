<div class="container">
	<div class="pagination">
		<?php echo $pagination->getPageLinks(); ?>
	</div>

	<table class="table">
		<tr>
			<th><?php echo $text_url; ?> <?php echo $pagination->getSortUrls('url'); ?></th>
			<th><?php echo $text_title; ?> <?php echo $pagination->getSortUrls('title'); ?></th>
			<th><?php echo $text_permissions; ?> <?php echo $pagination->getSortUrls('permissions'); ?></th>
			<th class="align-center"><?php echo $text_description; ?> <?php echo $pagination->getSortUrls('description'); ?></th>
			<th class="align-center"><?php echo $text_keywords; ?> <?php echo $pagination->getSortUrls('keywords'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($pages as $controller => $method) { ?>
			<tr>
				<td><?php echo $method['url']; ?></td>
				<td><?php echo $method['title']; ?></td>
				<td><?php echo $method['permissions']; ?></td>
				<td class="align-center">
					<?php if ($method['description']) { ?>
						<i class="icon-thumbs-up"></i>
					<?php } else { ?>
						<i class="icon-thumbs-down"></i>
					<?php } ?>
				</td>
				<td class="align-center">
					<?php if ($method['keywords']) { ?>
						<i class="icon-thumbs-up"></i>
					<?php } else { ?>
						<i class="icon-thumbs-down"></i>
					<?php } ?>
				</td>
				<td>
					<a href="<?php echo $this->url->getURL('administrator/Pages', 'edit', [$method['controller'], $method['main_method'], $method['sub_method']]); ?>" class="btn btn-primary"><i class="icon-edit-sign" title="<?php echo $text_edit; ?>"></i></a>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>
