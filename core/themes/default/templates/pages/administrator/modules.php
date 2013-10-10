<div class="container">
	<table class="table">
		<tr>
			<th><?php echo $text_name; ?></th>
			<th><?php echo $text_description; ?></th>
			<th class="align-center"><?php echo $text_installed; ?></th>
			<th class="align-center"><?php echo $text_enabled; ?></th>
			<th></th>
		</tr>
		<?php foreach ($modules as $module) { ?>
			<tr>
				<td><?php echo $module['name']; ?></td>
				<td><?php echo $module['description']; ?></td>
				<td class="align-center">
					<?php if ($module['installed']) { ?>
						<i class="icon-thumbs-up"></i>
					<?php } else { ?>
						<a href="<?php echo $module['install_url']; ?>" title="<?php echo $text_install_module; ?>"><i class="icon-thumbs-down"></i></a>
					<?php } ?>
				</td>
				<td class="align-center">
					<?php if ($module['enabled']) { ?>
						<a href="<?php echo $module['disable_url']; ?>" title="<?php echo $text_disable_module; ?>"><i class="icon-thumbs-up"></i></a>
					<?php } else { ?>
						<a href="<?php echo $module['enable_url']; ?>" title="<?php echo $text_enable_module; ?>"><i class="icon-thumbs-down"></i></a>
					<?php } ?>
				</td>
				<td>
					<?php if ($module['enabled']) { ?>
						<a href="<?php echo $this->url->getURL($module['config_controller']); ?>" class="btn btn-primary"><i class="icon-edit-sign" title="<?php echo $text_edit; ?>"></i></a>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>
