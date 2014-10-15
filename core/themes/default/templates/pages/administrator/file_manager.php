<link rel="stylesheet" type="text/css" href="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/file_manager/jqueryFileTree.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/jquery.contextMenu/jquery.contextMenu.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/file_manager/file_manager.css'); ?>" />

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
	<form name="search" id="search" method="get">
		<div>
			<input type="text" value="" name="q" id="q" />
			<a id="reset" href="#" class="q-reset"></a>
			<span class="q-inactive"></span>
		</div>
	</form>
	<div id="splitter">
		<div id="filetree"></div>
		<div id="fileinfo">
			<h1></h1>
		</div>
	</div>

	<script type="text/javascript">
		var file_manager_site_url = '<?php echo $site_url; ?>';
	</script>
	<script type="text/javascript" src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/file_manager/jquery.form.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/file_manager/jquery.splitter.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/file_manager/jqueryFileTree.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/jquery.contextMenu/jquery.contextMenu.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/jquery.impromptu/jquery-impromptu.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/jquery.tablesorter/js/jquery.tablesorter.min.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/file_manager/filemanager.min.js'); ?>"></script>
</div>
