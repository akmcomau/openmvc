<div class="container">
	<table class="table">
		<tr>
			<th><?php echo $text_url; ?></th>
			<th><?php echo $text_title; ?></th>
			<th><?php echo $text_permissions; ?></th>
			<th></th>
		</tr>
		<?php foreach ($pages as $controller => $method) { ?>
			<tr>
				<td><?php echo $method['url']; ?></td>
				<td><?php echo $method['title']; ?></td>
				<td><?php echo $method['permissions']; ?></td>
				<td>
					<a href="<?php echo $this->url->getURL('administrator/MetaData');?>" class="btn btn-primary" title="<?php echo $text_meta_data; ?>"><i class="icon-anchor"></i></a>
					<a href="" class="btn btn-primary"><i class="icon-edit-sign" title="<?php echo $text_edit; ?>"></i></a>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>
