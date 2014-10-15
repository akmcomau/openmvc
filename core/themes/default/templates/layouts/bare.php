<!DOCTYPE html>
<html>
<head>
	<title><?php echo $meta_tags['title']; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link rel="shortcut icon" href="<?php echo $this->url->getStaticUrl('/core/themes/default/images/favicon.ico'); ?>" />
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?php echo $this->url->getStaticUrl('/core/themes/default/images/icon_logo_114.gif'); ?>" />
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?php echo $this->url->getStaticUrl('/core/themes/default/images/icon_logo_72.gif'); ?>" />
	<link rel="apple-touch-icon-precomposed" href="<?php echo $this->url->getStaticUrl('/core/themes/default/images/icon_logo_57.gif'); ?>" />
</head>
<body>
	<div id="main-content">
		<?php echo $page_content; ?>
	</div>
</body>
</html>
