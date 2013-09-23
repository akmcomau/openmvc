<!DOCTYPE html>
<html>
<head>
	<title><?php echo $this->config->getSiteParams()->name; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/bootstrap.min.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/theme.css" rel="stylesheet" media="screen" />
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/bootstrap.min.js"></script>

	<link rel="shortcut icon" href="/core/themes/default/images/favicon.ico" />
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/core/themes/default/images/icon_logo_114.gif" />
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/core/themes/default/images/icon_logo_72.gif" />
	<link rel="apple-touch-icon-precomposed" href="/core/themes/default/images/icon_logo_57.gif" />
</head>
<body>
    <div class="navbar navbar-fixed-top navbar-inverse" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="<?php echo $this->url->getURL(); ?>"><?php echo $this->config->getSiteParams()->name; ?></a>
			</div>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li class="active"><a href="<?php echo $this->url->getURL(); ?>">Home</a></li>
					<li><a href="<?php echo $this->url->getURL('Information', 'aboutUs'); ?>">About</a></li>
					<li><a href="<?php echo $this->url->getURL('Information', 'contactUs'); ?>">Contact</a></li>
					<li><a href="<?php echo $this->url->getURL('Account'); ?>">My Account</a></li>
					<li><a href="<?php echo $this->url->getURL('Cart'); ?>">My Cart</a></li>
				</ul>
			</div>
		</div>
    </div>

	<div id="main-content">
		<?php echo $page_content; ?>
	</div>

	<hr />
	<footer>
		<p>&copy; Company 2013</p>
	</footer>
</body>
</html>
