<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?php echo $meta_tags['title']; ?></title>

	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/packages/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/packages/font-awesome/css/font-awesome.min.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/packages/font-awesome/css/font-awesome-ie7.min.css" rel="stylesheet" media="screen" />

	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/common.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/admin.css" rel="stylesheet" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/admin_responsive.css" rel="stylesheet" />

	<script src="<?php echo $static_prefix; ?>/core/themes/default/packages/jquery/jquery.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/packages/bootstrap/js/bootstrap.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/form_validator.js"></script>

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
	<script src="<?php echo $static_prefix; ?>/core/themes/default/packages/ckeditor/ckeditor.js"></script>

	<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

	<link rel="shortcut icon" href="/core/themes/default/images/favicon.ico" />
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/core/themes/default/images/icon_logo_114.gif" />
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/core/themes/default/images/icon_logo_72.gif" />
	<link rel="apple-touch-icon-precomposed" href="/core/themes/default/images/icon_logo_57.gif" />

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
					<i class="icon-cog"></i>
				</button>
				<a class="navbar-brand" href="<?php echo $this->url->getURL('Administrator');?>"><?php echo $this->config->siteConfig()->name; ?></a>
			</div>
			<?php if ($administrator_logged_in) { ?>
				<div class="collapse navbar-collapse navbar-ex1-collapse">
					<?php $user_menu->echoBootstrapMenu(); ?>
				</div>
			<?php } ?>
		</div>
	</nav>

	<?php if ($logged_in) { ?>
		<div class="subnavbar">
			<div class="subnavbar-inner">
				<div class="container">
					<a href="javascript:;" class="subnav-toggle" data-toggle="collapse" data-target=".subnav-collapse">
						<span class="sr-only"><?php echo $text_toggle_navigation; ?></span>
						<i class="icon-reorder"></i>
					</a>
					<div class="collapse subnav-collapse">
						<?php $main_menu->echoBootstrapMenu(); ?>
					</div>
				</div>
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
