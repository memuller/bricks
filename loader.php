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
	
	
	if(in_array('list_table', $features) && !class_exists('WP_List_Table')) 
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	if(in_array('haml', $features) && !function_exists('display_haml')) 
		require_once __DIR__. '/vendors/haml/HamlParser.class.php' ;

	if(in_array('recaptcha', $features))
		require_once __DIR__. '/vendors/recaptcha-php/recaptchalib.php';
	
	if(in_array('ganon', $features) && !function_exists('file_get_dom')) 
		require __DIR__ . '/vendors/ganon.php' ;

	if(in_array('rest_post', $features)) 
		require_once __DIR__. '/RestPost.php' ;

	if(in_array('pest-json', $features) && !class_exists('PestJSON')) 
		require_once __DIR__. '/vendors/pest/PestJSON.php' ;
	
	require realpath(__DIR__. '/../base/Base.php') ;
	$base = $namespace."\Plugin";
	$base::build();

?>