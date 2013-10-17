<link rel="stylesheet" type="text/css" href="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.filetree/jqueryFileTree.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.contextmenu/jquery.contextMenu.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $static_prefix; ?>/core/themes/default/css/file_manager.css" />

<div class="container">
	<form id="uploader" method="post">
		<button id="home" name="home" type="button" value="Home">&nbsp;</button>
		<h1></h1>
		<div id="uploadresponse"></div>
		<input id="mode" name="mode" type="hidden" value="add" />
		<input id="currentpath" name="currentpath" type="hidden" />
		<div id="file-input-container">
			<div id="alt-fileinput">
				<input	id="filepath" name="filepath" type="text" /><button id="browse" name="browse" type="button" value="Browse"></button>
			</div>
			<input	id="newfile" name="newfile" type="file" />
		</div>
		<button id="upload" name="upload" type="submit" value="Upload"></button>
		<button id="newfolder" name="newfolder" type="button" value="New Folder"></button>
		<button id="grid" class="ON" type="button">&nbsp;</button>
		<button id="list" type="button">&nbsp;</button>
	</form>
	<div id="splitter">
		<div id="filetree"></div>
		<div id="fileinfo">
			<h1></h1>
		</div>
	</div>
	<form name="search" id="search" method="get">
		<div>
			<input type="text" value="" name="q" id="q" />
			<a id="reset" href="#" class="q-reset"></a>
			<span class="q-inactive"></span>
		</div>
	</form>

	<ul id="itemOptions" class="contextMenu">
		<li class="select"><a href="#select"></a></li>
		<li class="download"><a href="#download"></a></li>
		<li class="rename"><a href="#rename"></a></li>
		<li class="delete separator"><a href="#delete"></a></li>
	</ul>

	<script type="text/javascript">
		var file_manager_site_url = '<?php echo $site_url; ?>';
	</script>
	<script type="text/javascript" src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.form.js"></script>
	<script type="text/javascript" src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.splitter/jquery.splitter.js"></script>
	<script type="text/javascript" src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.filetree/jqueryFileTree.js"></script>
	<script type="text/javascript" src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.contextmenu/jquery.contextMenu.js"></script>
	<script type="text/javascript" src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.impromptu.min.js"></script>
	<script type="text/javascript" src="<?php echo $static_prefix; ?>/core/themes/default/js/jquery.tablesorter.min.js"></script>
	<script type="text/javascript" src="<?php echo $static_prefix; ?>/core/themes/default/js/file_manager/filemanager.min.js"></script>
</div>