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
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/admin_theme.css" rel="stylesheet" media="screen" />

	<script src="<?php echo $static_prefix; ?>/core/themes/default/packages/jquery/jquery.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/packages/bootstrap/js/bootstrap.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/packages/ckeditor/ckeditor.js"></script>

	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/form_validator.js"></script>

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
			<?php if ($logged_in) { ?>
				<div class="collapse navbar-collapse navbar-ex1-collapse">
					<ul class="nav navbar-nav navbar-right">
						<li><a href="<?php echo $this->url->getURL();?>"><i class="icon-home"></i> <?php echo $text_public_site; ?></a></li>
						<li class="dropdown">
							<a href="javscript:;" class="dropdown-toggle" data-toggle="dropdown">
								<i class="icon-user"></i>
								<?php echo $administrator_logged_in['administrator_first_name'].' '.$administrator_logged_in['administrator_last_name']; ?>
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
							<li><a href="<?php echo $this->url->getURL('administrator/Administrators', 'edit', [$administrator_logged_in['administrator_id']]); ?>"><?php echo $text_profile; ?></a></li>
								<li class="divider"></li>
								<li><?php echo $this->url->getLink('', 'Administrator', 'logout'); ?></li>
							</ul>
						</li>
					</ul>
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
						<ul class="mainnav">
							<li class="active">
								<a href="<?php echo $this->url->getURL('Administrator');?>">
									<i class="icon-home"></i>
									<span><?php echo $text_home; ?></span>
								</a>
							</li>
							<li class="dropdown">
								<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
									<i class="icon-copy"></i>
									<span><?php echo $text_content; ?></span>
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<li class="dropdown-submenu">
										<a href="<?php echo $this->url->getURL('administrator/Pages');?>"><?php echo $text_pages; ?></a>
										<ul class="dropdown-menu">
											<li><?php echo $this->url->getLink('', 'administrator/Pages');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/Pages', 'add');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/CategoryManager', 'page');?></li>
										</ul>
									</li>
									<li class="dropdown-submenu">
										<a href="<?php echo $this->url->getURL('administrator/Blocks');?>"><?php echo $text_blocks; ?></a>
										<ul class="dropdown-menu">
											<li><?php echo $this->url->getLink('', 'administrator/Blocks');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/Blocks', 'add');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/CategoryManager', 'block');?></li>
										</ul>
									</li>
									<li><?php echo $this->url->getLink('', 'administrator/Modules');?></li>
									<li><?php echo $this->url->getLink('', 'administrator/LanguageEditor');?></li>
									<li><?php echo $this->url->getLink('', 'administrator/FileManager');?></li>
								</ul>
							</li>
							<li class="dropdown">
								<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
									<i class="icon-user"></i>
									<span><?php echo $text_users; ?></span>
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<li class="dropdown-submenu">
										<a href="<?php echo $this->url->getURL('administrator/Administrators');?>"><?php echo $text_administrators; ?></a>
										<ul class="dropdown-menu">
											<li><?php echo $this->url->getLink('', 'administrator/Administrators');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/Administrators', 'add');?></li>
										</ul>
									</li>
									<li class="dropdown-submenu">
										<a href="<?php echo $this->url->getURL('administrator/Customers');?>"><?php echo $text_customers; ?></a>
										<ul class="dropdown-menu">
											<li><?php echo $this->url->getLink('', 'administrator/Customers');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/Customers', 'add');?></li>
										</ul>
									</li>
								</ul>
							</li>
						</ul>
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
<script type="text/javascript">
	<?php if ($this->config->siteConfig()->enable_latex) { ?>
		MathJax.Hub.Config({
			tex2jax: {inlineMath: [["$","$"],["\\(","\\)"]]}
		});
	<?php } ?>
</script>
</html>
