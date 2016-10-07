<script src="<?php echo $this->url->getStaticUrl('/core/themes/default/packages/ace-builds/src-noconflict/ace.js'); ?>"></script>
<div class="container">
	<div class="row">
		<div class="col-md-12">
			<h1><?php echo $text_edit_file; ?></h1>
		</div>
	</div>
	<form class="admin-form" method="post" id="form-edit-file">
		<div class="row">
			<div class="col-md-12">
				<div class="widget">
					<div class="widget-header">
						<h3><?php echo htmlspecialchars($sub_path); ?></h3>
					</div>
					<div class="widget-content">
						<div id="file_editor" style="height: 600px;"><?php echo htmlspecialchars(file_get_contents($path.$sub_path)) ?></div>
						<input type="hidden" name="file_content" value ="" />
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 align-right">
				<button class="btn btn-primary" type="submit" name="form-edit-file-submit">
					<?php echo $text_update_file; ?></button>
				<br /><br />
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
	<?php echo $message_js; ?>

	file_editor = ace.edit("file_editor");
	file_editor.setTheme("ace/theme/dreamweaver");
	file_editor.getSession().setMode("ace/mode/php");

	$("#form-edit-file button[name='form-edit-file-submit']").click(function(event) {
		$('input[name="file_content"]').val(file_editor.getValue());
	});
</script>
