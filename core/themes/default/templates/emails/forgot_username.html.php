<p>
Hi <?php echo htmlspecialchars((string)$name); ?>,
</p>

<p>
Your username for <?php echo $this->config->siteConfig()->name; ?> is "<?php echo htmlspecialchars((string)$username); ?>".
</p>

<p>
Regards,<br />
<?php echo $this->config->siteConfig()->name; ?>
</p>
