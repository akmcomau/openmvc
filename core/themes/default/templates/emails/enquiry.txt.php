<?php echo $text_hi; ?>,

<?php echo $text_email_line; ?>


<?php foreach ($fields as $property => $var) {
	echo $property.': '. $var."\n";
} ?>

<?php echo $text_regards; ?>,
<?php echo $this->config->siteConfig()->name; ?>

