<?php

	class BasePlugin {

		static $db_version = 0 ;
		static $custom_posts = array();
		static $custom_users = array();
		static $custom_classes = array();
		static $custom_singles = array();
		static $custom_taxonomies = array();
		static $restricted_menus = array();

		static $roles = array(
		);

		static $absent_roles = array();

		static function path($path){
			return plugin_dir_path(dirname(__FILE__)). $path;
		}

		static function url($url){
			return plugin_dir_url(dirname(__FILE__)). $url ;
		}

		static function build(){
			$base = get_called_class(); $namespace = '\\'.get_namespace($base) . '\\';
			foreach (array_merge(static::$custom_classes, static::$custom_posts, static::$custom_singles, static::$custom_taxonomies, static::$custom_users) as $object) {
				require( static::path('models/'. $object . '.php'));
				$class = $namespace. ucfirst($object);
				$class::build();

			}
			require static::path('presenters/Base.php'); 

			add_action('plugins_loaded', function() use($base, $namespace) {
				$prefix = strtolower(str_replace('\\', '', $namespace));
				$db_version = get_option( $prefix.'_db_version', '0');

				if( floatval($db_version) < $base::$db_version) {
					if(! empty($base::$custom_taxonomies)) \CustomTaxonomy::build_database();
						
					foreach (array_merge($base::$custom_classes, $base::$custom_users) as $class) {
						$class = $namespace. $class ;
						$class::build_database();
					}

					if(!empty($base::$absent_roles) || false ){
						foreach ($base::$absent_roles as $role) {
							remove_role($role);
						}
					}
				}	
			} );

			if(!empty(static::$restricted_menus)){
				$restricted_menus = static::$restricted_menus;
				add_action('admin_menu', function() use ($restricted_menus){
					if(!current_user_can('manage_options')){
						global $menu ; $restricted = array();
						foreach ($restricted_menus as $item) {
							$restricted[]= __($item);
						}
						end ($menu);
						while (prev($menu)){
							$value = explode(' ',$menu[key($menu)][0]);
							if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
						}
					}
				} );
			}

			add_action('save_post', function($post_id) use($base, $namespace) {
				
				if( defined(DOING_AUTOSAVE) && DOING_AUTOSAVE) return ;

				if(isset($_POST['custom_single']) && in_array($_POST['custom_single'], $base::$custom_singles) ){
					$class = $namespace.$_POST['custom_single']; $object = $_POST[$_POST['custom_single']];
				}

				if( in_array(ucfirst($_POST['post_type']), $base::$custom_posts )){
					$object = $_POST[$_POST['post_type']]; $class = $namespace. ucfirst($_POST['post_type']);	
				}

				if(!isset($object)) return ;
				
				foreach ($class::$fields as $field_name => $field_options) {
					if(isset($object[$field_name]) ){
						update_post_meta($post_id, $field_name, $object[$field_name]) ;
					} elseif ($class::$fields[$field_name]['type'] == 'boolean') {
						update_post_meta($post_id, $field_name, 0);
					}
				}
			});

			add_action('admin_enqueue_scripts', function() use ($base, $namespace) {
				wp_enqueue_style(__NAMESPACE__.'-admin', $base::url('css/admin/main.css') );
				$screen = get_current_screen() ;
				if( $screen->base == 'edit-tags' && in_array(ucfirst($screen->taxonomy), $base::$custom_taxonomies )){
					$name = $screen->taxonomy ;
				} elseif (in_array(ucfirst($screen->post_type), $base::$custom_posts)) {
					$name = $screen->post_type;
				}
				if(isset($name)){
					wp_enqueue_script( $name, $base::url( "js/admin/$name.js") );
					wp_enqueue_style( $name, $base::url( "css/admin/$name.css") );
					$class = $namespace.ucfirst($name);
					
					foreach ($class::$fields as $field => $options) {
						if( 'date' == $options['type'] ){
							wp_enqueue_script('jquery-datepick', $base::url('lib/js/jquery-datepick/jquery.datepick.js'), array('jquery'));
							wp_enqueue_script('jquery-datepick-br', $base::url('lib/js/jquery-datepick/jquery.datepick-pt-BR.js'), array('jquery-datepick'));
							wp_enqueue_style('jquery-datepick', $base::url('lib/js/jquery-datepick/smoothness.datepick.css'));
							wp_enqueue_script('datepicker', $base::url('lib/js/utils/datepicker.js'), array('jquery-datepick-br'));
						}
						
						if ('file' == $options['type']) {
							wp_enqueue_script('file_upload', $base::url('lib/js/utils/file_upload.js'), array('jquery'));
						}

						if( 'geo' == $options['type']){
							wp_enqueue_script('jquery-ui-autocomplete', array('jquery'));
							wp_enqueue_script('gmaps-api', 'http://maps.google.com/maps/api/js?sensor=false&language=pt-BR', array('jquery')) ;
							wp_enqueue_script('geo-field', $base::url('lib/js/utils/geo-field.js'), array('jquery', 'gmaps-api'));
						}
					}
				}

			});
			
			add_action('admin_print_scripts', function(){
				$screen = get_current_screen();
				if($screen->base = 'media-upload' && isset($_GET['force_insert']) && $_GET['force_insert'] == 'true' ){ ?>
					<script>
						jQuery(function($){
							$('#tab-gallery').hide();
						});
					</script>
				<?php }
			}, 99);

			add_action('get_media_item_args', function($args){
				
				if ( isset( $_GET['force_insert'] ) && 'true' == $_GET['force_insert'] ){
					$args['send'] = true; $args['delete'] = false; $args['toggle']= false; 
				} 				
				if ( isset( $_POST['attachment_id'] ) && '' != $_POST["attachment_id"] )
					$args['send'] = true;	
				?>
				<script>
					function get_parameter_by_name(name) {
						name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
						var regexS = "[\\?&]" + name + "=([^&#]*)";
						var regex = new RegExp(regexS);
						var results = regex.exec(window.location.href);
						if(results == null)
							return "";
						else
							return decodeURIComponent(results[1].replace(/\+/g, " "));
					}
					jQuery(function($){
						if(get_parameter_by_name('force_insert') == 'true'){
							$('.slidetoggle.describe tbody tr').not('.submit').hide();
							$('.savesend .wp-post-thumbnail').hide();
							$('.savesend .button').val(get_parameter_by_name('label'));
							$('.ml-submit').hide();
						}
					});
				</script>
				
				<?php return $args ; 
			});

			if(!empty(static::$roles)){
				add_action('current_screen', function() use($base) {
					global $current_user ;
					if(isset($base::$roles[$current_user->roles[0]])){
						if(isset($base::$roles[$current_user->roles[0]]['landing_page']) ){
							$screen = get_current_screen();
							if($screen->id == 'dashboard' ) wp_redirect(admin_url( $base::$roles[$current_user->roles[0]]['landing_page'] ));
						}
						if(isset($base::$roles[$current_user->roles[0]]['collapse_menu'])){
							add_action('admin_enqueue_scripts', function() use($base) {
								wp_enqueue_script('collapse-menu', $base::url('lib/js/admin/utils/collapse_menu.js'), array('jquery'));
							});
						}
					}
				});

			}

		}

	}

 ?>