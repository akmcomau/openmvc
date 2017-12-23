<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?php echo $meta_tags['title']; ?></title>

	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet" />
	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/font-awesome/css/font-awesome.min.css'); ?>" rel="stylesheet" media="screen" />
	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/jqueryui/themes/base/jquery-ui.css'); ?>" rel="stylesheet" media="screen" />

	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/css/bootstrap.css'); ?>" rel="stylesheet" media="screen" />
	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/css/common.css'); ?>" rel="stylesheet" media="screen" />
	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/css/admin.css'); ?>" rel="stylesheet" media="screen" />

	<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/jquery/jquery.min.js'); ?>"></script>
	<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/jqueryui/jquery-ui.js'); ?>"></script>
	<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/bootstrap/dist/js/bootstrap.min.js'); ?>"></script>
	<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/js/form_validator.js'); ?>"></script>

	<?php if ($this->config->siteConfig()->enable_latex) { ?>
		<script src="<?php echo $this->config->siteConfig()->enable_latex; ?>"></script>
		<script type="text/javascript">
		  MathJax.Hub.Config({
			  tex2jax: {
				  inlineMath: [["$","$"],["\\(","\\)"]]
			  }
		  });
		</script>
	<?php } ?>
	<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/ckeditor/ckeditor.js'); ?>"></script>

	<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/html5shiv/dist/html5shiv.min.js'); ?>"></script>
		<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/Respond/dest/respond.min.js'); ?>"></script>
	<![endif]-->

	<link rel="shortcut icon" href="<?php echo $this->url->getStaticUrl('/core/themes/default/images/favicon.ico'); ?>" />
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?php echo $this->url->getStaticUrl('/core/themes/default/images/icon_logo_114.gif'); ?>" />
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?php echo $this->url->getStaticUrl('/core/themes/default/images/icon_logo_72.gif'); ?>" />
	<link rel="apple-touch-icon-precomposed" href="<?php echo $this->url->getStaticUrl('/core/themes/default/images/icon_logo_57.gif'); ?>" />

	<?php foreach ($meta_tags as $property => $value) {
		echo '<meta property="'.$property.'" content="'.htmlspecialchars($value).'" />';
	} ?>
</head>

<body>
	<nav class="navbar navbar-inverse" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
					<span class="sr-only"><?php echo $text_toggle_navigation; ?></span>
					<i class="fa fa-cog"></i>
				</button>
				<a class="navbar-brand" href="<?php echo $this->url->getUrl();?>"><?php echo $this->config->siteConfig()->name; ?></a>
			</div>
			<?php if ($administrator_logged_in) { ?>
				<div class="collapse navbar-collapse navbar-ex1-collapse">
					<?php $user_menu->echoBootstrapMenu(); ?>
				</div>
			<?php } ?>
		</div>
	</nav>

	<?php if ($administrator_logged_in) { ?>
		<div class="navbar-default">
			<div class="container">
				<nav role="navigation">
					<div class="container">
						<a href="javascript:;" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex2-collapse">
							<span class="sr-only"><?php echo $text_toggle_navigation; ?></span>
							<i class="fa fa-reorder"></i>
						</a>
						<div class="collapse navbar-collapse navbar-ex2-collapse">
							<?php $main_menu->echoBootstrapMenu(); ?>
						</div>
					</div>
				</nav>
			</div>
		</div>
	<?php } ?>
	<div id="notifications_area" class="container">
	</div>

	<div id="main-content">
		<?php echo $page_content; ?>
	</div>

	<div class="footer">
		<div class="container">
			<div class="row">
				<div id="footer-copyright" class="col-md-12 align-center">
					<?php echo $text_footer_text; ?>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
