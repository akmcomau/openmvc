<p>
Hi <?php echo htmlspecialchars((string)$name); ?>,
</p>

<p>
Please follow this link to reset your password: <br />
<a href="<?php echo htmlspecialchars((string)$url); ?>"><?php echo htmlspecialchars((string)$url); ?></a>
</p>

<p>
Regards,<br />
<?php echo $this->config->siteConfig()->name; ?>
</p>
