<?php 
	class BasePost {

		static $fields = array() ;
		static $taxonomies = array();
		public $post; 

		function __get($name){
			global $post ;

			if($name == 'id') $name = 'ID';

			if(strstr($name, '-')){
				list($name, $attribute) = explode('-', $name) ;
			}

			if(isset($this->unfiltered_fields[$name])){
				return isset($attribute) ? property_or_key($this->apply_filters($name), $attribute) : $this->apply_filters($name) ;

			}

			if(in_array($name, static::$taxonomies)){
				return $this->get_term_attributes($name, $attribute) ;
			}

			if(isset(static::$fields[$name])) {
				$this->unfiltered_fields[$name] = get_post_meta($post->ID, $name, true) ;
				return $this->apply_filters($name) ;
			} else {
				return isset($this->post) ? $this->post->$name : $post->$name ; 
			}
		}

		function __construct($post=false){
			$this->unfiltered_fields = array();
			if($post){
				if(is_numeric($post)){
					$post = get_post($post) ;
				}
			} else {
				$post = $GLOBALS['post'] ;
			}
			$this->post = $post ;
			
			foreach(get_post_custom($post->ID) as $field_name => $field_values){
				if(isset(static::$fields[$field_name])){
					$this->unfiltered_fields[$field_name] = $field_values[0] ;
				}
			}
			
		}

		function apply_filters($field){
			switch (static::$fields[$field]['type']) {
				case 'array':
					return maybe_unserialize($this->unfiltered_fields[$field]) ;
					break;
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