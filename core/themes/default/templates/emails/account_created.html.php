<p><?php echo $text_hi; ?> <?php echo $name; ?>,</p>

<p><?php echo $text_your_account_created; ?></p>
<p>
<?php echo $text_url;?>:
<a href="<?php echo $this->config->getSiteURL().$this->url->getUrl('Customer', 'login'); ?>"><?php echo $this->config->getSiteURL().$this->url->getUrl('Customer', 'login'); ?></a><br />
<?php echo $text_username.': '.$username; ?>
</p>
<p>
<?php echo $text_regards; ?>,<br />
<?php echo $this->config->siteConfig()->name; ?>
</p>
