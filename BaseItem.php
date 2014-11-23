<?php 
	class BaseItem {

		static $fields = array() ;
		public $unfiltered_fields = array();
		public $base ; 
		public $valid = true;
		static $meta_type = 'post';

		function __get($name){
			if($name == 'permalink') return get_permalink($this->base->ID);
			if($name == 'id') $name = 'ID';
			if($name == 'title') $name = 'post_title';
			if($name == 'content') $name = 'post_content';
			if($name == 'post_format') $name = '_revision_post_format' ;

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
				$this->unfiltered_fields[$name] = $value ;
			}
		}

		function __call($function, $params){
			if(!empty(static::$has) || isset(static::$belongs_to)){
				foreach (static::$has as $item) {
					if($function == $item || $function == $item.'s'){
						return $this->children( $item,
							isset($params[0]) ? $params[0] : 1,
							isset($params[1]) ? $params[1] : get_option('posts_per_page')
						);
					}
				}
				if(strpos($function, '_')!== -1){
					$exploded_function = explode('_', $function);
					if( isset(static::$has)  && in_array($exploded_function[0], static::$has)){
						$respondable = array() ;
						$items = $this->children( $exploded_function[0], 1, 4 );
						foreach ($items as $item) {
							$respondable[]= sprintf(
								"<a href='%s'>%s</a>",
								admin_url("post.php?post=$item->ID&action=edit"),
								$item->post_title
							);
						}
						if(!empty($respondable))
							$respondable[]= sprintf(
								"<em><a href='%s'>(...)</a></em>",
								admin_url(sprintf("edit.php?post_type=%s&post_parent=%d", $exploded_function[0], $this->ID ))
							);
						return  !empty($respondable) ? implode('<br/>', $respondable) : '—' ;
						
					} elseif($exploded_function[0] == static::$belongs_to ) {

						$parent_class = sibling_class(ucfirst(static::$belongs_to), get_called_class());
						$field = $exploded_function[0];
						if($this->$field){
							$parent = new $parent_class($this->$field);
							return sprintf("<a href='%s'>%s</a>",
									admin_url("post.php?post=$parent->ID&action=edit"),
									$parent->post_title);	
						} else { return '--' ; }
						
					} else {
						return false ;
					}
				}
				
			
			
			} else {
				return false ;
			}
		}

		function __construct($base=false){
			if($base){
				if('post' == static::$meta_type && is_numeric($base)) $base = get_post($base) ;
				if('user' == static::$meta_type){
					if(is_numeric($base)){
						$base = get_userdata($base);
					} elseif (is_string($base)) {
						$base = get_user_by( 'login', $base );
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

		function parent(){
			$field = static::$belongs_to ;
			$parent_class = sibling_class(ucfirst($field), get_called_class());
			return new $parent_class($this->$field);
		}

		function children($type, $page = 1, $per_page = null, $build = true){
			$class = get_called_class(); $namespace = get_namespace($class);
			if(!isset($per_page)) $per_page = get_option('posts_per_page');
			$results = get_posts(array('post_type' => $type,
				'paged' => $page,
				'posts_per_page' => $per_page,
				'meta_query' => array(
					array(
						'key' => static::$name,
						'value' => $this->ID
					)
				)
			));
			if($build){
				$child_class = $namespace.'\\'.ucfirst($type);
				$returnable = array();
				foreach ($results as $result) {
					$returnable[]= new $child_class($result);
				}
				return $returnable ; 
			} else { return $results ;}
		}

		function apply_filters($field){
			if(!isset($this->unfiltered_fields[$field])){ 
				if(isset(static::$fields[$field]['default'])) 
					$this->unfiltered_fields[$field] = static::$fields[$field]['default'] ; 
			}
			switch (static::$fields[$field]['type']) {
				case 'geo':
				case 'array':
				case 'multiple':
					if(!maybe_unserialize($this->unfiltered_fields[$field])) return array();
					return (array) maybe_unserialize($this->unfiltered_fields[$field]) ;
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