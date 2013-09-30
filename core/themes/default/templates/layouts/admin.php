<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?php echo $meta_tags['title']; ?></title>

	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/bootstrap.min.css" rel="stylesheet" media="screen" />
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/bootstrap.min.js"></script>

	<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/font_awesome.min.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/font_awesome_ie7.min.css" rel="stylesheet" media="screen" />

	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/admin.css" rel="stylesheet" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/admin_responsive.css" rel="stylesheet" />
    <link href="<?php echo $static_prefix; ?>/core/themes/default/css/jquery_ui.css" rel="stylesheet">

	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/admin_theme.css" rel="stylesheet" media="screen" />
	<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/bootstrap.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/form_validator.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.flot.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.flot.pie.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.flot.resize.js"></script>

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
					<span class="sr-only">Toggle navigation</span>
					<i class="icon-cog"></i>
				</button>
				<a class="navbar-brand" href="<?php echo $this->getURL('Administrator');?>"><?php echo $this->config->siteConfig()->name; ?></a>
			</div>
			<?php if ($logged_in) { ?>
				<div class="collapse navbar-collapse navbar-ex1-collapse">
					<ul class="nav navbar-nav navbar-right">
						<li class="dropdown">
							<a href="javscript:;" class="dropdown-toggle" data-toggle="dropdown">
								<i class="icon-user"></i>
								Rod Howard
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
								<li><?php echo $this->url->getLink('', 'Administrator', 'account_settings'); ?></li>
								<li><?php echo $this->url->getLink('', 'Administrator', 'change_password'); ?></li>
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
								<a href="<?php echo $this->getURL('Administrator');?>">
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
										<a href="javascript:;"><?php echo $text_pages; ?></a>
										<ul class="dropdown-menu">
											<li><?php echo $this->url->getLink('', 'administrator/Pages');?></li>

											<li><?php echo $this->url->getLink('', 'administrator/MetaData');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/CategoryManager', 'index', []);?></li>
									</ul>
									<li class="dropdown-submenu">
										<a href="javascript:;"><?php echo $text_latex; ?></a>
										<ul class="dropdown-menu">
											<li><?php echo $this->url->getLink('', 'administrator/Latex');?></li>

											<li><?php echo $this->url->getLink('', 'administrator/Latex', 'combinations');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/CategoryManager', 'index', []);?></li>
										</ul>
									</li>
									<li><?php echo $this->url->getLink('', 'administrator/LanguageEditor');?></li>
									<li><?php echo $this->url->getLink('', 'administrator/FileManager');?></li>
								</ul>
							</li>
							<li class="dropdown">
								<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
									<i class="icon-th"></i>
									<span><?php echo $text_products; ?></span>
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<li><?php echo $this->url->getLink('', 'administrator/Products', 'index');?></li>
									<li><?php echo $this->url->getLink('', 'administrator/Products', 'add');?></li>
									<li><?php echo $this->url->getLink('', 'administrator/CategoryManager', 'index', []);?></li>
									<li><?php echo $this->url->getLink('', 'administrator/Products', 'attributes');?></li>
								</ul>
							</li>
							<li class="dropdown">
								<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
									<i class="icon-th-list"></i>
									<span><?php echo $text_subscriptions; ?></span>
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<li><?php echo $this->url->getLink('', 'administrator/Subscriptions', 'index');?></li>
									<li><?php echo $this->url->getLink('', 'administrator/Subscriptions', 'types');?></li>
								</ul>
							</li>
							<li class="dropdown">
								<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
									<i class="icon-th-list"></i>
									<span><?php echo $text_users; ?></span>
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<li><?php echo $this->url->getLink('', 'administrator/Customers', 'index');?></li>
									<li><?php echo $this->url->getLink('', 'administrator/Administrators', 'index');?></li>
								</ul>
							</li>
							<li class="dropdown">
								<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
									<i class="icon-external-link"></i>
									<span><?php echo $text_reports; ?></span>
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<li class="dropdown-submenu">
										<a tabindex="-1" href="#"><?php echo $text_reports_orders; ?></a>
										<ul class="dropdown-menu">
											<li><?php echo $this->url->getLink('', 'administrator/ReportOrders', 'index');?></li>

											<li><?php echo $this->url->getLink('', 'administrator/ReportOrders', 'sales');?></li>
											<li><?php echo $this->url->getLink('', 'administrator/ReportOrders', 'products');?></li>
										</ul>
									</li>
									<li><?php echo $this->url->getLink('', 'administrator/ReportSubscriptions', 'index');?></li>
								</ul>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class="main">
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
