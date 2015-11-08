<?php  
	class CustomSingle extends CustomPost {
		static $name = "home" ;
		static $post_type = 'page';
		static $skip_creation = true ;
		static $custom_single = true ;


		static function construct(){
			$class = get_called_class();
			$post_type_class = sibling_class(ucfirst(static::$post_type), $class);
			$post_type_class::$fields = array_merge( $post_type_class::$fields, static::$fields );
			$post_type_class::$editable_by = array_merge($post_type_class::$editable_by, static::$editable_by); 
			
			add_action('edit_form_after_title', function() use($class, $post_type_class){
				global $post;
				echo "<input type='hidden' name='custom_single' value='".ucfirst($class::$name)."'>";
			});
		}

		static function build(){
			$class = get_called_class();
			$post_type_class = sibling_class(ucfirst(static::$post_type), $class);
			if(class_exists($post_type_class)){
				static::$fields = array_merge( $post_type_class::$fields, static::$fields );
				static::$editable_by = array_merge($post_type_class::$editable_by, static::$editable_by); 
				
			}

			if(is_admin()){
				add_action('current_screen', function() use($class, $post_type_class){
					
					$screen = get_current_screen();
					if($screen->post_type == $class::$post_type &&
						isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['post'])
					){
						$post = get_post($_GET['post']);
						if ($post->post_name == $class::$name){	
							$class::construct();
							global $custon_single;
							$custon_single = $class;
						}	
					}
					
				}, 0, -10);	
			}
		}

	}
 ?>