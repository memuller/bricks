<?php

	class CustomPost extends BasePost {

		static $name ;
		static $creation_fields ;
		static $editable_by = array();
		static $collumns = array();
		static $actions = array();
		static $absent_collumns = array();
		static $absent_actions = array('quick-edit');		
		static function create_post_type(){
			register_post_type( static::$name, static::$creation_fields ) ;
		}		
		
		static function build(){
			$class = get_called_class();
			add_action('init', $class.'::create_post_type' ) ;
			
			$class = get_called_class();
			add_action('init', $class.'::create_post_type' ) ;
			$editable_by = $class::$editable_by ; $fields = $class::$fields ; 
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
						$presenter::render('admin/defaults/metabox', array( 'type' => $class::$name, 'object' => $object, 'fields' => $fields_to_use ));
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
							if(in_array($field, $options['fields'])){
								$fields_to_use = array_merge($fields_to_use, array($field => $field_options));
								unset($fields[$field]);
							}
						}
						$placing = isset($options['placing']) ? $options['placing'] : 'side';
						add_meta_box($class::$name.'-'.$metabox, $options['name'] , function() use ($class, $fields_to_use) {
							$object = new $class(); $presenter = get_namespace($class).'\Presenters\Base'; 
							$presenter::render('admin/defaults/metabox', array( 'type' => $class::$name, 'object' => $object, 'fields' => $fields_to_use ));
						}, $class::$name, $placing, 'high');
					}

				});
			}

			// Sets custom list view collumns.
			if(! empty(static::$collumns)){
				foreach (static::$collumns as $name => $label) {
					add_filter('manage_edit-'.$class::$name.'_columns', function($collumns) use($name, $label) {
						if(! isset($collumns[$name])) $collumns[$name] = $label ;
						return $collumns;
					});

					add_action('manage_'.$class::$name.'_posts_custom_column', function($collumn_name) use($class, $name){
						$object = new $class();
						if($collumn_name == $name){
							echo method_exists($object, $name) ? $object->$name() : $object->$name ;
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
								$link = sprintf('edit.php?post_type=%s&id=%s&action=%s&page=%s', $class::$name, $object->id, $action, $action);
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
						add_filter('post_row_actions', function($actions) use ($class) {
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

		}
		
	}

 ?>