<div class="wrap">
	<h1><?php echo $class::$title ?></h1>
	<form method='post' action='<?php echo "options.php" ?>'>
		<?php settings_fields($class::option_name()) ?>
			<?php $presenter::render('admin/metabox', [
				'type' => $class::option_name(), 'object' => (object) $class::options(),
				'fields' => $class::$fields,
			]); ?>

		<?php submit_button(); ?>
	</form>
</div>