<?php
# Requires vendored libs and base structure.
	
	if(!isset($features)) 
		$features = array();

	if(!isset($namespace))
		$namespace = ucfirst(basename(realpath(__DIR__.'/..')));

	if(!class_exists('BasePlugin')) require_once __DIR__ . '/BasePlugin.php' ;
	if(!class_exists('Presenter')) require_once __DIR__ . '/Presenter.php' ;
	if(!class_exists('BaseItem')) require_once __DIR__ . '/BaseItem.php' ;
	if(!class_exists('BasePost')) require_once __DIR__ . '/BasePost.php' ;
	if(!class_exists('DB_Object')) require_once __DIR__ . '/DB_Object.php' ;
	if(!class_exists('CustomPost')) require_once __DIR__ . '/CustomPost.php' ;
	if(!class_exists('CustomPostFormat')) require_once __DIR__ . '/CustomPostFormat.php' ;
	if(!class_exists('CustomTaxonomy')) require_once __DIR__ . '/CustomTaxonomy.php' ;
	if(!class_exists('CustomUser')) require_once __DIR__ . '/CustomUser.php' ;
	if(!class_exists('Translation')) require_once __DIR__ . '/Translation.php' ;
	
	
	if(isset($features['list_table']) && !class_exists('WP_List_Table')) 
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	if(isset($features['haml']) && !function_exists('display_haml')) 
		require_once __DIR__. '/vendors/haml/HamlParser.class.php' ;

	if(isset($features['recaptcha']))
		require_once __DIR__. '/vendors/recaptcha-php/recaptchalib.php';
	
	if(isset($features['ganon']) && !function_exists('file_get_dom')) 
		require __DIR__ . '/vendors/ganon.php' ;
	
	require realpath(__DIR__. '/../base/Base.php') ;
	$base = $namespace."\Plugin";
	$base::build();

?>