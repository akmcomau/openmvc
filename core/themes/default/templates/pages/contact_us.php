<?php $site = $this->config->siteConfig(); ?>
<div class="<?php echo $page_class; ?>">
	<div class="row">
		<div class="col-md-12">
			<form id="form-contact-us" action="<?php echo $this->url->getUrl('Root', 'contactUsSend'); ?>" method="post">
				<?php if ($site->contact_enable_recaptcha) { ?>
					<input type="hidden" name="g-recaptcha-response" />
					<input type="hidden" name="form-contact-us-submit" value="1" />
					<div role="alert" id="contact-recaptcha-error"><?php if (isset($form->getErrors()['recaptcha'])) echo htmlspecialchars($text_error_recaptcha); ?></div>
				<?php } ?>
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
<?php if ($site->contact_enable_recaptcha) { ?>
<script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($site->contact_recaptcha_key); ?>"></script>
<script>
document.getElementById('form-contact-us').addEventListener('submit', function (event) {
	event.preventDefault();
	const form = this;
	if (form.dataset.pending) return;
	form.dataset.pending = '1';
	function failed() {
		delete form.dataset.pending;
		document.getElementById('contact-recaptcha-error').textContent = <?php echo json_encode($text_error_recaptcha, JSON_HEX_TAG); ?>;
	}
	if (!window.grecaptcha || !grecaptcha.enterprise) return failed();
	grecaptcha.enterprise.ready(function () {
		grecaptcha.enterprise.execute(<?php echo json_encode($site->contact_recaptcha_key, JSON_HEX_TAG); ?>, {action: 'CONTACT'}).then(function (token) {
			if (!token) return failed();
			form.elements['g-recaptcha-response'].value = token;
			HTMLFormElement.prototype.submit.call(form);
		}, failed);
	});
});
</script>
<?php } ?>
