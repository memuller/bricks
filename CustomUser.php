<?php

	class CustomUser {

		static $name ;
		static $label;
		static $inherits_from = 'editor';
		static $capabilities = array();

		static $actions = array();
		static $absent_actions = array('quick-edit');
		
		static $absent_fields = array();
		static $fields = array();
		

		static function build_database(){
			remove_role(static::$name);
			$inherits_from = get_role( static::$inherits_from ); 
			
			if( !empty(static::$capabilities) ){
				$capabilities = array_merge($inherits_from->capabilities, static::$capabilities); 
				
				$admin = get_role('administrator');
				foreach (static::$capabilities as $capability) {
					$admin->add_cap($capability);
				}

			} else { $capabilities = $inherits_from->capabilities ; }
			
			add_role(static::$name, static::$label, $capabilities );
		}

		static function build(){
			$class = get_called_class();
			$presenter = get_namespace($class).'\Presenters\Base';

			if(! empty(static::$fields)){
				foreach (array('show_user_profile', 'edit_user_profile') as $hook) {
					add_action($hook, function($user) use($class, $presenter){
						if(in_array($class::$name, $user->roles) ){
							$object = new $class(); $fields_to_use = $class::$fields ;
							$presenter::render('admin/defaults/metabox', array( 'type' => $class::$name, 'object' => $object, 'fields' => $fields_to_use ));
						}
					});
				}
			}

		}
		
	}

 ?>