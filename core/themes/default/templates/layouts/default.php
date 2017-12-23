<!DOCTYPE html>
<html>
<head>
	<title><?php echo $meta_tags['title']; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet" />
	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/font-awesome/css/font-awesome.min.css'); ?> rel="stylesheet" media="screen" />

	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/css/bootstrap.css'); ?>" rel="stylesheet" media="screen" />
	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/css/common.css'); ?>" rel="stylesheet" media="screen" />
	<link href="<?php echo $this->url->getStaticUrl('/core/themes/default/css/theme.css'); ?>" rel="stylesheet" media="screen" />

	<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/jquery/jquery.min.js'); ?>"></script>
	<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/bootstrap/js/bootstrap.min.js'); ?>"></script>
	<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/js/form_validator.js'); ?>"></script>

	<link rel="canonical" href="<?php echo $request->currentUrl(); ?>" />

	<?php if ($this->config->siteConfig()->enable_latex) { ?>
		<script src="<?php echo $this->config->siteConfig()->enable_latex; ?>"></script>
		<script type="text/javascript">
			MathJax.Hub.Config({
				tex2jax: {inlineMath: [["$","$"],["\\(","\\)"]]}
			});
		</script>
	<?php } ?>

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

	<?php if ($this->config->siteConfig()->contenttools_page_edit && $administrator_logged_in) { ?>
		<script type="text/javascript">
			var edit_url = '<?php echo $this->url->getUrl('administrator\Pages', 'edit', [$controller_name, $method_name, $sub_page, 'save']); ?>';
		</script>
		<link href="http://cdn.jsdelivr.net/contenttools/1.3.1/content-tools.min.css" rel="stylesheet" media="screen" />
		<script src="http://cdn.jsdelivr.net/contenttools/1.3.1/content-tools.min.js"></script>
		<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/js/content-tools.js'); ?>"></script>
	<?php } ?>
</head>
<body>
<?php if ($administrator_logged_in) { ?>
	<nav class="navbar navbar-inverse" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex2-collapse">
					<span class="sr-only"><?php echo $text_toggle_navigation; ?></span>
					<i class="fa fa-cog"></i>
				</button>
				<a class="navbar-brand" href="<?php echo $this->url->getUrl('Administrator');?>"><?php echo $text_administrator_panel; ?></a>
			</div>
			<div class="collapse navbar-collapse navbar-ex1-collapse">
				<?php $admin_panel->echoBootstrapMenu(); ?>
			</div>
		</div>
	</nav>
<?php } ?>
<div id="navigation" class="wrapper">
	<div class="navbar-static-top">
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
						<form action="<?php echo $this->url->getUrl('Root', 'search'); ?>">
							<input type="text" class="float-left" name="search" value="" />
							<input type="submit" class="btn btn-primary btn-small hidden-sm hidden-xs float-left" value="<?php echo $text_search_button;?>" />
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="navbar-default">
				<nav role="navigation">
					<div class="btn-navbar navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
							<i class="fa fa-reorder"></i>
						</button>
					</div>
					<div class="collapse navbar-collapse navbar-ex1-collapse">
						<?php $main_menu->echoBootstrapMenu(); ?>
						<?php $user_menu->echoBootstrapMenu(); ?>
					</div>
				</nav>
			</div>
		</div>
	</div>
	<div id="notifications_area" class="container">
	</div>


	<div id="modern-browsers" class="container"><noscript>
		<div class="row-fluid modern-browsers">
			<hr />
			<h2>You require a modern browser with JavaScript enabled to view this site</h2>
			<h4>If you are using Internet Explorer 9 or higher, please disable Compatibility View.</h4>
			<h4>Or download and install one of these browsers:</h4>
			<div class="row modern-browsers">
				<div class="col-md-1"></div>
				<div class="col-md-2 col-sm-6 align-center">
					<a href="http://www.google.com/chrome">
						<img src="<?php echo $this->url->getStaticUrl('/core/themes/default/images/browsers/chrome.jpg'); ?>" />
						<h5>Google Chome</h5>
					</a>
				</div>
				<div class="col-md-2 col-sm-6 align-center">
					<a href="http://www.mozilla.org/firefox">
						<img src="<?php echo $this->url->getStaticUrl('/core/themes/default/images/browsers/firefox.jpg'); ?>" />
						<h5>Mozilla Firefox</h5>
						<br />
					</a>
				</div>
				<div class="col-md-2 col-sm-6 align-center">
					<a href="http://www.apple.com/safari/">
						<img src="<?php echo $this->url->getStaticUrl('/core/themes/default/images/browsers/safari.jpg'); ?>" />
						<h5>Safari</h5>
					</a>
				</div>
				<div class="col-md-2 col-sm-6 align-center">
					<a href="http://windows.microsoft.com/en-us/internet-explorer/download-ie">
						<img src="<?php echo $this->url->getStaticUrl('/core/themes/default/images/browsers/ie.jpg'); ?>" />
						<h5>Internet Explorer</h5>
					</a>
				</div>
			</div>
		</div>
		<hr />
	</noscript></div>

	<div id="main-content" class="<?php echo isset($page_class) ? $page_class : ''; ?>" <?php if ($editable) echo 'data-editable data-name="main-content"'; ?>>
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
								<p><abbr title="Phone"><i class="fa fa-phone"></i></abbr> <?php echo $text_footer_phone; ?></p>
								<p><abbr title="Email"><i class="fa fa-envelope"></i></abbr> <?php echo $this->config->siteConfig()->email_addresses->contact_us; ?></p>
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
						<a href="#"><i class="fa fa-twitter"></i></a>
						<a href="#"><i class="fa fa-facebook"></i></a>
						<a href="#"><i class="fa fa-linkedin"></i></a>
						<a href="#"><i class="fa fa-google-plus"></i></a> </div>

						<ul class="list-inline align-center">
							<li><?php echo $this->url->getLink('', 'Root', 'page/terms'); ?></li>
							<li><?php echo $this->url->getLink('', 'Root', 'page/privacy'); ?></li>
							<li><?php echo $this->url->getLink('', 'Root', 'contactUs'); ?></li>
						</ul>
					</div>
				</div>
				<div class="row-fluid">
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

		$(document).ready(function(){
			var test_canvas = document.createElement("canvas");
			var canvascheck = (test_canvas.getContext)? true : false;
			if (!canvascheck) {
				$('#modern-browsers').html($('#modern-browsers noscript').text());
				$('#navigation a').each(function() {
					$(this).attr('href', '#').unbind('click').removeAttr('data-toggle');
				});
				return;
			}
		});

		<?php if ($this->config->siteConfig()->enable_latex) { ?>
			MathJax.Hub.Config({
				tex2jax: {inlineMath: [["$","$"],["\\(","\\)"]]}
			});
		<?php } ?>
	</script>
</div>
</body>
</html>
