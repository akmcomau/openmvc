<div class="<?php echo $page_class; ?>">
	<div class="row">
		<div class="col-md-12">
			<form id="form-contact-us" action="<?php echo $this->url->getUrl('Root', 'contactUsSend'); ?>" method="post">
				<input type="hidden" name="send_message" value="1" />
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo $text_contact_us; ?></h3>
					</div>
					<div class="widget-content">
						<p><?php echo $text_contact_note; ?></p>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_name; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($form->getValue('name')); ?>" />
								<?php echo $form->getHtmlErrorDiv('name'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_email; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<input type="text" name="email" class="form-control" value="<?php echo htmlspecialchars($form->getValue('email')); ?>" />
								<?php echo $form->getHtmlErrorDiv('email'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_enquiry; ?></div>
							<div class="col-md-9 col-sm-9 ">
								<textarea name="enquiry" class="form-control"><?php echo htmlspecialchars($form->getValue('enquiry')); ?></textarea>
								<?php echo $form->getHtmlErrorDiv('enquiry'); ?>
							</div>
						</div>
						<hr class="separator-2column" />
						<div class="row">
							<div class="col-md-12 align-center">
								<button type="submit" name="form-contact-us-submit" class="btn btn-lg btn-primary"><?php echo $text_send; ?></button>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>
</script>
