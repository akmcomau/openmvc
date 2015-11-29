<div class="container">
	<table class="table modules">
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
						<?php if ($module['enabled_anywhere']) { ?>
							<i class="fa fa-thumbs-up"></i>
						<?php } else { ?>
							<a href="<?php echo $module['uninstall_url']; ?>" title="<?php echo $text_uninstall_module; ?>" onclick="return confirm('<?php echo htmlspecialchars($text_confirm_uninstall); ?>')"><i class="fa fa-thumbs-up"></i></a>
						<?php } ?>
					<?php } else { ?>
						<a href="<?php echo $module['install_url']; ?>" title="<?php echo $text_install_module; ?>" onclick="return confirm('<?php echo htmlspecialchars($text_confirm_install); ?>')"><i class="fa fa-thumbs-down"></i></a>
					<?php } ?>
				</td>
				<td class="align-center">
					<?php if ($module['installed']) { ?>
						<?php if ($module['enabled']) { ?>
							<a href="<?php echo $module['disable_url']; ?>" title="<?php echo $text_disable_module; ?>" onclick="return confirm('<?php echo htmlspecialchars($text_confirm_disable); ?>')"><i class="fa fa-thumbs-up"></i></a>
						<?php } else { ?>
							<a href="<?php echo $module['enable_url']; ?>" title="<?php echo $text_enable_module; ?>" onclick="return confirm('<?php echo htmlspecialchars($text_confirm_enable); ?>')"><i class="fa fa-thumbs-down"></i></a>
						<?php } ?>
					<?php } else { ?>
						<i class="fa fa-thumbs-down"></i>
					<?php } ?>
				</td>
				<td>
					<?php if ($module['enabled']) { ?>
						<a href="<?php echo $this->url->getUrl($module['config_controller'], 'config'); ?>" class="btn btn-primary"><i class="fa fa-edit" title="<?php echo $text_edit; ?>"></i></a>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>
