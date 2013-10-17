<!DOCTYPE html>
<html>
<head>
	<title><?php echo $meta_tags['title']; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/bootstrap.min.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/navigation.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/colour_blue.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/common.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/theme.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/font_awesome.min.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/font_awesome_ie7.min.css" rel="stylesheet" media="screen" />
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/bootstrap.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/form_validator.js"></script>
	<script type="text/javascript" src="<?php echo $static_prefix; ?>/core/themes/default/mathjax/MathJax.js?config=TeX-AMS_HTML-full"></script>
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
<?php if ($administrator_logged_in) { ?>
	<nav class="navbar navbar-inverse" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex2-collapse">
					<span class="sr-only"><?php echo $text_toggle_navigation; ?></span>
					<i class="icon-cog"></i>
				</button>
				<a class="navbar-brand" href="<?php echo $this->url->getURL('Administrator');?>"><?php echo $text_administrator_panel; ?></a>
			</div>
			<div class="collapse navbar-collapse navbar-ex1-collapse">
				<ul class="nav navbar-nav navbar-right">
					<li><a href="<?php echo $this->url->getURL('Administrator');?>"><i class="icon-cog"></i> <?php echo $text_admin_site; ?></a></li>
					<li><a href="<?php echo $this->url->getURL('administrator/LanguageEditor', 'edit', $language_files);?>"><i class="icon-copy"></i> <?php echo $text_admin_language; ?></a></li>
					<li><a href="<?php echo $this->url->getURL('administrator/Pages', 'edit', $method);?>"><i class="icon-anchor"></i> <?php echo $text_admin_edit_page; ?></a></li>
					<li><a href="<?php echo $this->url->getURL('Administrator', 'logout');?>"><i class="icon-user"></i> <?php echo $text_admin_logout; ?></a></li>
				</ul>
			</div>
		</div>
	</nav>
<?php } ?>
<div id="navigation" class="wrapper">
	<div class="navbar  navbar-static-top">
		<div class="header">
			<div class="header-inner container">
				<div class="row-fluid">
					<div class="col-md-4 col-sm-4 col-xs-12 brand">
						<a href="/" title="Home">
							<h1><?php echo $this->config->siteConfig()->name; ?></h1>
						</a>
					</div>
					<div class="col-md-4 col-sm-4 hidden-xs">
						<?php echo $text_slogan;?>
					</div>
					<div class="col-md-4 col-sm-4 col-xs-12 header-search">
						<a href="cart.html"><h4><?php echo $text_search;?></h4></a>
						<form action="<?php echo $this->url->getURL('Root', 'search'); ?>">
							<input type="text" class="float-left" name="search" value="" />
							<input type="submit" class="btn btn-primary btn-small hidden-sm hidden-xs float-left" value="<?php echo $text_search_button;?>" />
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="navbar navbar-inner">
				<nav role="navigation">
					<div class="btn-navbar navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
							<i class="icon-reorder"></i>
						</button>
					</div>
					<div class="collapse navbar-collapse navbar-ex1-collapse">
						<ul class="nav navbar-nav main-menu">
							<li><?php echo $this->url->getLink('menu-item'); ?></li>
							<li><?php echo $this->url->getLink('menu-item', 'Root', 'page/about_us'); ?></li>
							<li><?php echo $this->url->getLink('menu-item', 'Root', 'contactUs'); ?></li>
						</ul>
						<?php if ($customer_logged_in) { ?>
							<ul class="nav navbar-nav navbar-right main-menu">
								<li class="dropdown">
												  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $text_my_account; ?><b class="caret"></b></a>
									<ul class="dropdown-menu">
										<li><?php echo $this->url->getLink('menu-item', 'Customer'); ?></li>
										<li><?php echo $this->url->getLink('menu-item', 'Customer', 'contact_details'); ?></li>
										<li><?php echo $this->url->getLink('menu-item', 'Customer', 'change_password'); ?></li>
										<li class="divider"></li>
										<li><?php echo $this->url->getLink('menu-item', 'customer/Orders'); ?></li>
									</ul>
								</li>
								<li><?php echo $this->url->getLink('menu-item', 'Customer', 'logout'); ?></li>
							</ul>
						<?php } else { ?>
							<ul class="nav navbar-nav navbar-right main-menu">
								<li><?php echo $this->url->getLink('menu-item', 'Customer', 'register'); ?></li>
								<li><?php echo $this->url->getLink('menu-item', 'Customer', 'login'); ?></li>
							</ul>
						<?php } ?>
					</div>
				</nav>
			</div>
		</div>
	</div>

	<div id="main-content">
		<?php echo $page_content; ?>
	</div>

	<div class="container">
		<footer id="footer">
			<div class="container">
				<div class="row">
					<div class="col-md-4 col">
						<div class="block contact-block">
							<h3><?php echo $this->url->getLink('', 'Root', 'contactUs'); ?></h3>
							<address>
								<p><abbr title="Phone"><i class="icon-phone"></i></abbr> <?php echo $text_footer_phone; ?></p>
								<p><abbr title="Email"><i class="icon-envelope"></i></abbr> <?php echo $this->config->siteConfig()->email_addresses->contact_us; ?></p>
							</address>
						</div>
					</div>
					<div class="col-md-5 col">
						<div class="block">
							<h3><?php echo $this->url->getLink('', 'Root', 'page/about_us'); ?></h3>
							<p><?php echo $text_footer_about; ?></p>
						</div>
					</div>
					<div class="col-md-3 col">
						<div class="social-media">
						<a href="#"><i class="icon-twitter"></i></a>
						<a href="#"><i class="icon-facebook"></i></a>
						<a href="#"><i class="icon-linkedin"></i></a>
						<a href="#"><i class="icon-google-plus"></i></a> </div>

						<ul class="list-inline align-center">
							<li><?php echo $this->url->getLink('', 'Root', 'page/terms'); ?></li>
							<li><?php echo $this->url->getLink('', 'Root', 'page/privacy'); ?></li>
							<li><?php echo $this->url->getLink('', 'Root', 'contactUs'); ?></li>
						</ul>
					</div>
				</div>
				<div class="row-fluid">
					<div id="toplink"><a href="#top" class="top-link" title="Back to top"><?php echo $text_back_to_top; ?> <i class="icon-chevron-up"></i></a></div>
					<div class="subfooter">
						<div class="col-md-12">
							<p><?php echo $text_footer_text; ?></p>
						</div>
					</div>
				</div>
			</div>
		</footer>
	</div>

	<script type="text/javascript">
	  $('.show-hide').each(function() {
		  $(this).click(function() {
			  var state = 'open';
			  var target = $(this).attr('data-target');
			  var targetState = $(this).attr('data-target-state');
			  if (typeof targetState !== 'undefined' && targetState !== false) {
				  state = targetState;
			  }
			  if (state === 'undefined') {
				  state = 'open';
			  }

			  $(target).toggleClass('show-hide-'+ state);
			  $(this).toggleClass(state);
		  });
	  });
	</script>
</body>
</html>
