<?php

	class CustomTaxonomy {

		static $name ;
		static $creation_fields = array();
		static $settings;
		static $labels ;
		static $applies_to = array('post');
		static $parent = null ; 
		static $child = null ;
		static $tax = array();
		static $fields = array();
		public $term ;

		static function create_taxonomy(){
			static::$creation_fields = array_merge(static::$settings, array('labels' => static::$labels));
			register_taxonomy( static::$name, static::$applies_to, static::$creation_fields ) ;
		}	

		static function build(){
			$class = get_called_class();
			add_action('init', $class.'::create_taxonomy' ) ;
			if($class::$parent){
				$parent_class = get_namespace($class) ."\\". ucfirst($class::$parent) ;
				$class::$fields = array_merge(array( 
					$class::$parent => array('type' => 'term_taxonomy', 'taxonomy' => $class::$parent, 'label' => $parent_class::$labels['singular_name'], 'description' => $parent_class::$labels['description'])),
					$class::$fields 
				);
				if(is_admin()){
					add_filter('load-edit-tags.php', function() use($class) {
						$screen = get_current_screen();

						if($screen->id == 'edit-'.$class::$name && $screen->base == 'edit-tags'){
							global $posts, $tags, $taxonomy;
						}
					});
				}
					
			}

			foreach(array('add', 'edit') as $action){
				add_action($class::$name.'_'.$action.'_form_fields', function() use($class,$action){
					global $tag;
					$screen = get_current_screen();
					$object = new $class(); $presenter = get_namespace($class).'\Presenters\Base'; 
					$partial = $action == 'add' ? 'form_table' : 'metabox' ;
					$presenter::render("admin/defaults/$partial", array( 'type' => $class::$name, 'object' => $object, 'fields' => $class::$fields ));
				}, 10, 2);
			}

			add_action('edited_'.$class::$name, function($term_id) use($class){
				$term_taxonomy = get_term($term_id, $class::$name);
				foreach ($_POST[$class::$name] as $key => $value) {
					if(isset($class::$fields[$key])){
						if(in_array($class::$fields[$key]['type'], array('geo', 'list', 'array')))
							$value = maybe_serialize($value);
						update_tax_meta($term_taxonomy->term_taxonomy_id, $key, $value);
					}
				} 
				#$term = new $class($term_taxonomy->term_taxonomy_id); die(print_r($term->zones, true));
			});

			add_action('created_'.$class::$name, function($term_id) use($class){
				$term_taxonomy = get_term($term_id, $class::$name);
				foreach ($_POST[$class::$name] as $key => $value) {
					if(isset($class::$fields[$key])){
						if(in_array($class::$fields[$key]['type'], array('geo', 'list', 'array')))
							$value = maybe_serialize($value);
						update_tax_meta($term_taxonomy->term_taxonomy_id, $key, $value);
					}
				}
			});
		}

		static function build_database(){
			global $wpdb;
			require_once ABSPATH . 'wp-admin/includes/upgrade.php' ;

			$sql = sprintf("CREATE TABLE %s (
				meta_id bigint(20) unsigned auto_increment not null,
				term_taxonomy_id bigint(20) unsigned not null,
				meta_key varchar(255) null,
				meta_value varchar(255) null,
				primary key meta_id (meta_id),
				key term_taxonomy_id (term_taxonomy_id),
				key meta_key (meta_key)  
			);", $wpdb->prefix.'taxmeta' ) ;
			dbDelta($sql) ;
		}

		function __construct($arg = false){
			global $tag; 
			if($arg){
				if (is_numeric($arg)) {
					$this->term = get_term($arg, static::$name);

				}elseif(is_string($arg)) {
					$this->term = get_term_by('slug', $arg, static::$name );
				} else {
					$this->term = $arg ; 
				}
			} else {
				$this->term = &$tag;
			}
			if(!$this->term) $this->term = new stdClass() ;
		}

		function __get($name){

			if(static::$child && static::$child == $name){
				return $this->children();
			}

			if($name == 'id') $name = 'term_taxonomy_id';
			
			if(isset(static::$fields[$name])) {
				$field = get_tax_meta($this->term->term_taxonomy_id, $name, true);
				if(in_array(static::$fields[$name]['type'], array('geo', 'array', 'list'))) 
					$field = maybe_unserialize($field);
				if(!isset($field) || empty($field) && isset(static::$fields[$name]['default']))
					$field = static::$fields[$name]['default'];
				return $field;
			} else {
				return $this->term->$name ; 
			}
		}

		function __set($name, $value){
			if(isset(static::$fields[$name])){
				if(in_array(static::$fields[$name]['type'], array('geo', 'array', 'list'))) 
					$value = maybe_serialize($value);
				update_tax_meta($this->term['term_taxonomy_id'], $name, $value);
			}
		}
        
		function children(){
			if(static::$child){
				$returnable = array(); $child_class = get_namespace(get_called_class()).'\\'. ucfirst(static::$child); 
				global $wpdb ; 
				$tax = $wpdb->term_taxonomy ; $terms = $wpdb->terms ; $meta = $wpdb->prefix.'taxmeta' ;
				$sql = $wpdb->prepare("SELECT $tax.*, $terms.slug, $terms.name 
					from $tax 
						join $terms on $tax.term_id = $terms.term_id
						join $meta on $tax.term_taxonomy_id = $meta.term_taxonomy_id 

					where $meta.meta_key = %s and $meta.meta_value = '%d' ", static::$name, $this->term_id);
				$results = $wpdb->get_results($sql);
				foreach ($results as $result) {
					$returnable[]= new $child_class($result);
				}
				return $returnable ; 
			}
		} 
	}

function get_tax_meta($term_taxonomy_id, $key = "", $single = false){
	global $wpdb ;
	$table = $wpdb->prefix . 'taxmeta' ;
	if(!isset($term_taxonomy_id)) return null ;
	if(empty($key)){
		return $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value from $table
			where term_taxonomy_id = %d", $term_taxonomy_id), OBJECT_K);
	}

	$values = $wpdb->get_col($wpdb->prepare("SELECT meta_value from $table 
		where term_taxonomy_id = %d and meta_key = %s", $term_taxonomy_id, $key));

	return $single ? $values[0] : $values ;
}

function update_tax_meta($term_taxonomy_id, $key, $value, $prev_value = null){
	global $wpdb ;
	$table = $wpdb->prefix . 'taxmeta' ;
	$meta_id = $wpdb->get_col($wpdb->prepare("SELECT meta_id from $table
		where term_taxonomy_id = %d and meta_key = %s", $term_taxonomy_id, $key));
	
	if(empty($meta_id)){
		$wpdb->insert($table, array(
			'term_taxonomy_id' => $term_taxonomy_id,
			'meta_key' => $key,
			'meta_value' => $value
		));
		return $wpdb->insert_id;
	} else {
		$where_clausule = array('term_taxonomy_id' => $term_taxonomy_id, 'meta_key' => $key);
		$where_format = array("%d", "%s");
		if($prev_value){
			$where_clausule = array_merge($where_clausule, array('meta_value' => $prev_value));
			$where_format[]= "%s";
		}
		
		$returnable =  $wpdb->update($table, 
			array('meta_value' => $value),
			$where_clausule, "%s", $where_format
		);

		return $returnable;
	}


	if(empty($key)){
		return $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value from $table
			where term_taxonomy_id = %d", $term_taxonomy_id), OBJECT_K);
	}

	$values = $wpdb->get_col($wpdb->prepare("SELECT meta_value from $table 
		where term_taxonomy_id = %d and meta_key = %s", $term_taxonomy_id, $key));

	return $single ? $values[0] : $values ;
}

 ?>