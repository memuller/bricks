<?php 
	class BaseItem {

		static $fields = array() ;
		public $unfiltered_fields = array();
		public $base ; 
		public $valid = true;
		static $meta_type = 'post';

		function __get($name){
			if($name == 'id') $name = 'ID';
			if($name == 'content') $name = 'post_content';

			if(strstr($name, '-')){
				list($name, $attribute) = explode('-', $name) ;
			}

			if(isset($this->unfiltered_fields[$name])){
				return isset($attribute) ? property_or_key($this->apply_filters($name), $attribute) : $this->apply_filters($name) ;

			}

			if( 'post' == static::$meta_type &&  in_array($name, static::$taxonomies)){
				return $this->get_term_attributes($name, $attribute) ;
			}

			if(isset(static::$fields[$name])) {
				$this->unfiltered_fields[$name] = get_metadata(static::$meta_type, $this->base->ID, $name, true) ;
				return $this->apply_filters($name) ;
			} else {
				return $this->base->$name ; 
			}
		}

		function __set($name, $value){
			if(isset(static::$fields[$name])){
				update_metadata(static::$meta_type, $this->base->ID, $name, $value) ;
			}
		}

		function __construct($base=false){
			if($base){
				if('post' == static::$meta_type && is_numeric($base)) $base = get_post($base) ;
				if('user' == static::$meta_type){
					if(is_numeric($base)){
						$base = get_userdata($arg);
					} elseif (is_string($base)) {
						$base = get_user_by( 'slug', $base );
					}
				} 
			} else {
				$base = $GLOBALS[static::$meta_type] ;
			}
			$this->base = $base ;
			if(!isset($this->base) || !$this->base ){ 
				$this->valid = false ; return null ;
			} 
			$all_meta = 'post' == static::$meta_type ? get_post_custom($base->ID) : get_user_meta($base->ID) ; 
			foreach($all_meta as $field_name => $field_values){
				if(isset(static::$fields[$field_name])){
					$this->unfiltered_fields[$field_name] = $field_values[0] ;
				}
			}			
		}

		function apply_filters($field){
			switch (static::$fields[$field]['type']) {
				case 'geo':
				case 'array':
					return maybe_unserialize($this->unfiltered_fields[$field]) ;
					break;
				case 'bool':
				case 'integer':
					return intval($this->unfiltered_fields[$field]) ;
					break;
				default:
					return $this->unfiltered_fields[$field] ; 
					break;
			}
		}

		function get_term_attributes($taxonomy, $attribute='name'){
			if(empty($attribute)) $attribute = 'name' ;
			$terms = wp_get_object_terms($this->ID, $taxonomy) ;
			if(is_array($terms)){
				$returnable = array();
				foreach ($terms as $term) {
					$returnable[]= $term->$attribute ;
				}
				return implode(',' , $returnable) ;
			} else {
				return $terms->$attribute ;
			}
		}

		function date($field){
			if(static::$fields[$field]['type'] == 'date'){
				$date = explode('/', $this->$field) ;
				$date = sprintf("%s-%s-%s", $date[2], $date[1], $date[0]);
				return new DateTime($date) ;
			}
		}
	}
		
?>