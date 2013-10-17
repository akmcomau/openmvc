<div class="container">
	<form class="admin-form" method="post" id="form-language">
		<?php $counter = 0; ?>
		<?php foreach ($files as $file => $strings) { ?>
			<input type="hidden" name="files[]" value="<?php echo $file; ?>" />
			<div class="row">
				<div class="col-md-12">
					<div class="widget">
						<div class="widget-header">
							<h3><?php echo $file; ?></h3>
						</div>
						<div class="widget-content">
							<?php foreach ($strings as $tag => $string) { ?>
								<div class="row">
									<div class="col-md-12">
										<div class="col-md-4 col-sm-4 title-2column"><?php echo $tag; ?></div>
										<div class="col-md-8 col-sm-8 ">
											<textarea class="form-control" name="<?php echo $counter.'_'.$tag; ?>"><?php echo htmlspecialchars($string); ?></textarea>
											<?php echo $form->getHtmlErrorDiv($counter.'_'.$tag); ?>
										</div>
									</div>
								</div>
								<hr class="separator-2column" />
							<?php } ?>
							<div class="align-center">
								<button type="submit" class="btn btn-primary" name="form-language-submit"><?php echo $text_update; ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php $counter++; ?>
		<?php } ?>
	</form>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>
</script>
