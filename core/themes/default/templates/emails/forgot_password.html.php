<p>
Hi <?php echo $name; ?>,
</p>

<p>
Please follow this link to reset your password: <br />
<a href="<?php echo $url; ?>"><?php echo $url; ?></a>
</p>

<p>
Regards,<br />
<?php echo $this->config->siteConfig()->name; ?>
</p>
