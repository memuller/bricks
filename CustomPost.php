<?php

	class CustomPost extends BasePost {

		static $name ;
		static $creation_fields ;
		static $labels ;
		static $formats ;
		static $editable_by = array();
		static $collumns = array();
		static $actions = array();
		static $absent_collumns = array();
		static $absent_actions = array('quick-edit');
		static $hide_custom_fields = true ; 
		static $rateable = false ;
		static $belongs_to ;
		static $has = array(); 
		static $per_page = 10;
		static $fields = array();
		static $icon;

		static function create_post_type(){
			register_post_type( static::$name, static::$creation_fields ) ;
		}

		static function build(){
			$class = get_called_class(); $namespace = get_namespace($class); $domainspace = strtolower($namespace);
			if(isset(static::$labels)) static::$creation_fields['labels'] = static::$labels ; 
			$presenter = $namespace.'\Presenters\Base';
			
			if(isset($class::$formats)){
				$class::$fields['_revision_post_format'] = array('type' => 'hidden', 'required' => true, 'default' => static::$formats[0]);
				foreach ($class::$formats as $format) {
					$format = sibling_class(ucfirst($format), $class);
					$class::$fields = array_merge($class::$fields, $format::$fields);
					$class::$tabs[$format::$labels['singular_name']] = array_keys($format::$fields);
				}
				add_action('edit_form_advanced', function() use($class, $fields_to_use) {
					$screen = get_current_screen() ; 
					if($screen->post_type == $class::$name){
						global $post ; $format = get_post_meta( $post->ID, '_revision_post_format', true );
						if(!$format) $format = $class::$formats[0];
						echo '<input name="pedia[_revision_post_format]" id="_revision_post_format" value="'.$format.'" type="hidden">' ;
					}
				});
			}


			do_action( 'build_custom_post_formats-'.$class::$name);
			if('post' != $class::$name){
				add_action('init', $class.'::create_post_type' ) ;
			}
			$editable_by = $class::$editable_by ; $fields = $class::$fields ;
			//
			if(isset($class::$tabs)){
				add_action('edit_form_after_title', function() use($class, $presenter){
					$screen = get_current_screen();
					if($screen->post_type == $class::$name){
						$params = array(
							'presenter' => $presenter, 'tabs' => $class::$tabs, 
							'type' => $class::$name, 'class' => $class, 'object' => new $class(),
							
						);
						if(isset($class::$formats)){
							$params['data'] = $class::$formats ; 
						}
						$presenter::render('admin/tabbed', $params);
					}
				});
			}

			// Renders fields on an advanced form, if needed.
			if(in_array( 'form_advanced', array_keys(static::$editable_by) )){
				$fields_to_use = array();
				foreach ($class::$fields as $field => $options) {
					if(in_array($field, $class::$editable_by['form_advanced']['fields'] )){
						$fields_to_use = array_merge($fields_to_use, array($field => $options)  );
						unset($fields[$field]);
					}
				}
				add_action('edit_form_advanced', function() use($class, $fields_to_use) {
					$screen = get_current_screen() ; 
					if($screen->post_type == $class::$name){
						$object = new $class(); $presenter = get_namespace($class).'\Presenters\Base';
						$presenter::render('admin/metabox', array( 'type' => $class::$name, 'object' => $object, 'fields' => $fields_to_use, 'description_colspan' => false ));
					}
				});
				unset($editable_by['form_advanced']);
			}

			// Renders a main metabox, if needed.
			if(sizeof($editable_by) > 0 ){
				add_action('add_meta_boxes', function() use ($class, $fields, $editable_by) {
					foreach ($editable_by as $metabox => $options) {
						$fields_to_use = array();
						foreach($fields as $field => $field_options){
							if(in_array($field, loopable($options['fields']) )){
								$fields_to_use = array_merge($fields_to_use, array($field => $field_options));
								unset($fields[$field]);
							}
						}
						$placing = isset($options['placing']) ? $options['placing'] : 'side';
						$name = isset($options['name']) ? $options['name'] : ucfirst($metabox) ;
						add_meta_box($class::$name.'-'.$metabox, $name , function() use ($class, $fields_to_use, $metabox, $placing) {
							$object = new $class(); $presenter = get_namespace($class).'\Presenters\Base'; 
							$domain = strtolower(get_namespace($class));
							$table_hook = sprintf("%s-%s-%s-metabox-table", $domain, $class::$name, $metabox );
							$presenter::render('admin/metabox', array( 'type' => $class::$name, 'object' => $object, 'fields' => $fields_to_use, 'table_hook' => $table_hook, 'placing' => $placing ));
							do_action(sprintf("%s-%s-%s-metabox", $domain, $class::$name, $metabox));
						}, $class::$name, $placing, 'high');
					}

				});
			}


			if(!empty(static::$belongs_to)){
				$parent_class = sibling_class(ucfirst(static::$belongs_to), get_called_class());
				
				if(is_admin()){
					add_filter('pre_get_posts', function($query) use ($class){
						global $pagenow ;
						$vars = &$query->query_vars ; 
						if('edit.php' == $pagenow && $vars['post_type'] == $class::$name && isset($vars['post_parent']) ){
							$query->set('meta_key', $class::$belongs_to );
							$query->set('meta_value', $_GET['post_parent']  ); 
						}
					});
				}
			}

			// Sets custom list view collumns.
			if(! empty(static::$collumns)){
				foreach (static::$collumns as $name => $label) {

					add_filter('manage_edit-'.$class::$name.'_columns', function($collumns) use($name, $label) {
						if(! isset($collumns[$name])){
							$collumns[$name] = $label ;
							if(isset($collumns['date'])){
								unset($collumns['date']); $collumns['date'] = __('Date');
							}	
						} 
						return $collumns;
					});

					add_action('manage_'.$class::$name.'_posts_custom_column', function($collumn_name) use($class, $name){
						$object = new $class();
						if($collumn_name == $name){
							if( (isset($class::$belongs_to) && $name == $class::$belongs_to) || (isset($class::$has) && in_array($name, $class::$has)) ){
								$collumn_method = $name.'_collumn' ;
								echo $object->$collumn_method();
							} else {
								echo method_exists($object, $name) ? $object->$name() : $object->$name ;	
							}
						}
					});
				}
			}

			// Sets custom actions.
			if(!empty(static::$actions)){
				add_action( 'admin_head', function() use ($class){
					$screen = get_current_screen();
					if($screen->post_type == $class::$name){
						add_filter('post_row_actions', function($actions) use($class){
							$object = new $class();
							foreach ($class::$actions as $action => $options) {
								if(isset($options['capability']) && !current_user_can($options['capability']) ) continue ;
								if(isset($options['condition']) && !$object->$options['condition']() ) continue ;
								$link = sprintf('edit.php?post_type=%s&id=%s&action=%s', $class::$name, $object->id, $action, $action);
								$actions[$action] = sprintf("<a href='%s'>%s</a>", $link, $options['label']);									
								
							}
							return $actions;
						});
					}
				});
			}


			// Removes uneeded actions from list view.
			if(!empty(static::$absent_actions)){
				add_action('admin_head', function() use ($class){
					$screen = get_current_screen(); 
					if($screen->post_type == $class::$name){
						$filter = $class::$creation_fields['hierarchical'] ? 'page_row_actions' : 'post_row_actions' ;						
						add_filter($filter, function($actions) use ($class) {
							foreach ($class::$absent_actions as $name) {
								$name = $name == 'quick-edit' ? 'inline hide-if-no-js' : $name;
								unset($actions[$name]); 
							
							}
							return $actions ;
						});
					}
				});
			}

			// Removes uneeded collumns from list view.
			if(!empty(static::$absent_collumns)){
				foreach (static::$absent_collumns as $name) {
					add_filter('manage_edit-'.$class::$name.'_columns', function($collumns) use($name){
						unset($collumns[$name]); return $collumns;
					});
				}
			}

			if($class::$rateable){
				$class::$fields = array_merge($class::$fields, array(
					'ratings_number' => array('type' => 'integer', 'label' => 'Number of Ratings', 'default' => 0),
					'ratings_positive' => array('type' => 'integer', 'label' => 'Number of Negative Ratings', 'default' => 0),
					'ratings_negative' => array('type' => 'integer', 'label' => 'Number of Positive Ratings', 'default' => 0),
					'rating' => array('type' => 'integer', 'label' => 'Rating', 'default' => 0),
					'rated_by' => array('type' => 'array', 'label' => 'Users that rated this', 'default' => array())
				));
			}

			if(isset($class::$icon)){
				add_action('admin_print_scripts', function() use($class){
					printf('
					<style type="text/css">
						#menu-posts-%s .wp-menu-image:before{
							content: "%s" !important;
						}
					</style>', $class::$name, $class::$icon );
				});
			}
		}

		static function create($fields){
			global $wpdb ;
			$class = get_called_class() ;
			$meta = array(); $post = array('post_type' => static::$name, 'post_status' => 'publish');
			foreach ($fields as $field => $value) {
				if(array_key_exists($field, static::$fields)){
					$meta[$field] = $value ;
				} else {
					foreach (array('title', 'name', 'content', 'excerpt', 'author', 'name', 'status') as $name) {
					 	if($field == $name){
					 		$new_field = "post_$field" ;
					 		$post[$new_field] = $value ;
					 		unset($post[$field]) ;
					 		$field = $new_field ; 
					 	}
					 } 
					$post[$field] = $value ;
				}

			}

			$inserted = $wpdb->insert($wpdb->posts, $post);
			if(!$inserted) return false ;
			$post = new $class($wpdb->insert_id) ;
			foreach ($meta as $field => $value) {
				$post->$field = $value ;
			}
			return $post ; 
		}

		static function taxonomies(){
			return get_object_taxonomies(static::$name, 'objects');
		}

		public static function all($params = array()){
			$class = get_called_class();
			$default_params = array(
				'post_type' => static::$name,
				'posts_per_page' => static::$per_page
			);
			$params = array_merge($default_params, $params);
			
			if(isset($params['only'])){
				$params['posts_per_page'] = $params['only'];
				unset($params['only']);
			}
			
			if(isset($params['order_by_meta'])){
				$params['meta_key'] = $params['order_by_meta'];
				$params['order_by'] = 'meta_value' ;
				if(static::$fields['type'] == 'integer')
					$params['order_by'] .= '_num';

				unset($params['order_by_meta']);
			}
			foreach (static::taxonomies() as $taxonomy) {
				if(isset($params[$taxonomy->rewrite['slug']])){
					if(!isset($params['tax_query'])) $params['tax_query'] = array() ;
					$params['tax_query'][]= array(
						'taxonomy' => $taxonomy->query_var,
						'field' => 'slug',
						'terms' => loopable($params[$taxonomy->rewrite['slug']])
					); 
					unset($params[$taxonomy->rewrite['slug']]);
				}
			}
			return array_map(function($post) use($class) {
				return new $class($post);
			}, get_posts($params));
		}

		public function siblings($params = array()){
			$params = array_merge($params, array('post__not_in' => loopable($this->ID)));
			return static::all($params);
		}


		public function rate($value){
			if(!static::$rateable) return false ;
			if(static::$rateable === true) static::$rateable = array();
			static::$rateable = array_merge(array('self_rateable' => false, 'repeatable' => false), static::$rateable);

			if(is_user_logged_in()){
				$user = wp_get_current_user();
				if(!static::$rateable['self_rateable'] && $this->post_author == $user->ID) return false ; 
				if(!static::$rateable['repeatable'] && in_array($user->ID, $this->rated_by)) return false ;
				$this->rated_by = array_merge($this->rated_by, (array) $user->ID);
				
			}
			$value = (int) $value ; 
			$this->rating = $this->rating + $value ;
			$this->ratings_number = $this->ratings_number +1 ;
			if($value > 0){
				$this->ratings_positive = $this->ratings_positive +1 ;
			} else { $this->ratings_negative = $this->ratings_negative +1 ; }
			return true ; 
		}
		
	}

 ?>