<div class="container">
	<div class="row">
		<div class="col-md-12">
			<form class="admin-search-form" method="get" id="form-language-search">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $text_search; ?></h3>
					</div>
					<div class="widget-content">
						<div class="row">
							<div class="col-md-12">
								<div class="col-md-4 col-sm-4 title-2column"><?php echo $text_file; ?></div>
								<div class="col-md-6 col-sm-6 ">
									<input type="text" class="form-control" name="search_file" value="<?php echo htmlspecialchars($form->getValue('search_file')); ?>" />
									<?php echo $form->getHtmlErrorDiv('search_file'); ?>
								</div>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="align-right">
							<button type="submit" class="btn btn-primary" name="form-language-search-submit"><?php echo $text_search; ?></button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<div class="widget">
				<div class="widget-header">
					<h3><?php echo $text_search_results; ?></h3>
				</div>
				<div class="widget-content">
					<div class="pagination">
						<?php echo $pagination->getPageLinks(); ?>
					</div>
					<table class="table">
						<tr>
							<th><?php echo $text_file; ?> <?php echo $pagination->getSortUrls('file'); ?></th>
							<th><?php echo $text_string_count; ?> <?php echo $pagination->getSortUrls('count'); ?></th>
							<th></th>
						</tr>
						<?php foreach ($files as $file) { ?>
						<tr>
							<td><?php echo $file['file']; ?></td>
							<td><?php echo $file['count']; ?></td>
							<td>
								<a href="<?php echo $this->url->getURL('administrator/LanguageEditor', 'edit', [urlencode($file['file'])]); ?>" class="btn btn-primary"><i class="icon-edit-sign" title="<?php echo $text_edit; ?>"></i></a>
							</td>
						</tr>
						<?php } ?>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
