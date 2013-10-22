<?php

	class CustomUser extends BaseItem {

		static $name ;
		static $label;
		static $inherits_from = 'editor';
		static $capabilities = array();

		static $required_for = array();

		static $actions = array();
		static $absent_actions = array('quick-edit');
		
		static $absent_fields = array();
		static $fields = array();
		
		static $meta_type = 'user' ; 
		static $allow_admin = false ; 
		

		public function is_current(){
			global $current_user ; 
			return $this->user->ID == $current_user->ID ;
		}

		public function get($post_type, $args=array()){
			$default_args = array('post_type' => $post_type, 'author' =>$this->base->ID);
			return get_posts(array_merge($default_args, $args));
		}

		public function count($post_type = 'post'){
			global $wpdb ;
			$where = get_posts_by_author_sql($post_type, true, $this->ID) ;
			$sql = "SELECT count(*) from $wpdb->posts $where" ;
			return $wpdb->get_var( $sql );
		}

		public function join_date($format = "m/d/Y"){
			echo date($format, strtotime($this->user_registered));
		}

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

		static function auth(){
			auth_redirect();
		}

		function __construct($base=false){
			if(!$base){
				global $current_user ; 
				get_currentuserinfo();
				$base = $current_user ; 
			}
			parent::__construct($base);
		}

		static function build(){
			$class = get_called_class(); $namespace = get_namespace($class);
			$presenter = $namespace.'\Presenters\Base';
			$name = explode('\\', $class) ; $name = $name[sizeof($name)-1] ;
			$base = $namespace . '\Plugin';

			# Loads role permission requirements, if any.
			if(! empty(static::$required_for))
				$base::$role_requirements = array_merge($base::$role_requirements, array(static::$name => static::$required_for));

			if(! empty(static::$fields)){
				foreach (array('show_user_profile', 'edit_user_profile') as $hook) {
					add_action($hook, function($user) use($class, $presenter){
						if(in_array($class::$name, $user->roles) || ($class::$allow_admin && in_array('administrator', $user->roles)) ){
							$object = new $class(); $fields_to_use = $class::$fields ;
							$presenter::render('admin/defaults/metabox', array( 'type' => $class::$name, 'object' => $object, 'fields' => $fields_to_use, 'description_colspan' => false ));
						}
					});
				}
			}

		}
		
	}

 ?>