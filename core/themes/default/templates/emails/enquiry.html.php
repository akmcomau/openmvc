<p><?php echo $text_hi; ?>,</p>

<p><?php echo $text_email_line; ?></p>

<p>
<?php foreach ($fields as $property => $var) {
	echo $property.': '. $var."<br />";
} ?>
</p>

<p>
<?php echo $text_regards; ?>,<br />
<?php echo $this->config->siteConfig()->name; ?>
</p>
