<!DOCTYPE html>
<html>
<head>
	<title><?php echo $this->config->getSiteParams()->name; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/bootstrap.min.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/navigation.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/colour_blue.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/theme.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/font-awesome.min.css" rel="stylesheet" media="screen" />
	<link href="<?php echo $static_prefix; ?>/core/themes/default/css/font-awesome-ie7.min.css" rel="stylesheet" media="screen" />
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/bootstrap.min.js"></script>
	<script src="<?php echo $static_prefix; ?>/core/themes/default/js/form_validator.js"></script>

	<link rel="shortcut icon" href="/core/themes/default/images/favicon.ico" />
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/core/themes/default/images/icon_logo_114.gif" />
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/core/themes/default/images/icon_logo_72.gif" />
	<link rel="apple-touch-icon-precomposed" href="/core/themes/default/images/icon_logo_57.gif" />
</head>
<body>
<div id="navigation" class="wrapper">
	<div class="navbar  navbar-static-top">
		<div class="header">
			<div class="header-inner container">
				<div class="row-fluid">
					<div class="col-md-4 col-sm-4 col-xs-12 brand">
						<a href="/" title="Home">
							<h1><span>Open</span>MVC<span></span></h1>
						</a>
					</div>
					<div class="col-md-4 col-sm-4 hidden-xs">
						<a href="cart.html"><h4>Shopping Cart</h4></a>
						<a href="cart.html">2 item(s) - $40.00</a>
					</div>
					<div class="col-md-4 col-sm-4 col-xs-12 header-search">
						<a href="cart.html"><h4>Search</h4></a>
						<form action="<?php echo $this->url->getURL('Product', 'search'); ?>">
							<input type="text" class="float-left" name="search" value="" />
							<input type="submit" class="btn btn-primary btn-small hidden-sm hidden-xs float-left" value="Search" />
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
							<li><a class="menu-item" href="/">Home</a></li>
							<li><a class="menu-item" href="<?php echo $this->url->getURL('Information', 'aboutUs'); ?>">About Us</a></li>
							<li><a class="menu-item" href="<?php echo $this->url->getURL('Information', 'contactUs'); ?>">Contact Us</a></li>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown">Products <b class="caret"></b></a>
								<ul class="dropdown-menu">
									<li><a class="menu-item" href="#">Search Products</a></li>
									<li><a class="menu-item" href="#">Browse Products</a></li>
								</ul>
							</li>
							<li class="visible-xs"><a class="menu-item" href="<?php echo $this->url->getURL('Cart'); ?>">Shopping Cart</a></li>
							<li><a class="menu-item" href="<?php echo $this->url->getURL('Checkout'); ?>">Checkout</a></li>
						</ul>
						<?php if ($logged_in) { ?>
							<ul class="nav navbar-nav navbar-right main-menu">
								<li><a class="menu-item" href="<?php echo $this->url->getURL('Account'); ?>">My Account</a></li>
								<li><a class="menu-item" href="<?php echo $this->url->getURL('Account', 'logout'); ?>">Logout</a></li>
							</ul>
						<?php } else { ?>
							<ul class="nav navbar-nav navbar-right main-menu">
								<li><a class="menu-item" href="<?php echo $this->url->getURL('Account', 'register'); ?>">Signup</a></li>
								<li><a class="menu-item" href="<?php echo $this->url->getURL('Account', 'login'); ?>">Login</a></li>
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
							<h3>Contact Us</h3>
							<address>
								<p><abbr title="Phone"><i class="icon-phone"></i></abbr> +61 (12) 3456 7890</p>
								<p><abbr title="Email"><i class="icon-envelope"></i></abbr> info@openmvc.com</p>
							</address>
						</div>
					</div>
					<div class="col-md-5 col">
						<div class="block">
							<h3>About Us</h3>
							<p>Making the web a prettier place one template at a time! We make beautiful, quality, responsive Drupal & web templates!</p>
						</div>
					</div>
					<div class="col-md-3 col">
						<div class="social-media">
						<a href="#"><i class="icon-twitter"></i></a>
						<a href="#"><i class="icon-facebook"></i></a>
						<a href="#"><i class="icon-linkedin"></i></a>
						<a href="#"><i class="icon-google-plus"></i></a> </div>

						<ul class="list-inline align-center">
							<li><a href="<?php echo $this->url->getURL('Information', 'terms'); ?>">Terms</a></li>
							<li><a href="<?php echo $this->url->getURL('Information', 'privacy'); ?>">Privacy</a></li>
							<li><a href="<?php echo $this->url->getURL('Information', 'contactUs'); ?>">Contact Us</a></li>
						</ul>
					</div>
				</div>
				<div class="row-fluid">
					<div id="toplink"><a href="#top" class="top-link" title="Back to top">Back To Top <i class="icon-chevron-up"></i></a></div>
					<div class="subfooter">
						<div class="col-md-12">
							<p>Site Powered by <a href="http://www.openmvc.com">OpenMVC</a> | Copyright 2013 &copy; OpenMVC</p>
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
