<?php

	class BasePlugin {

		static $db_version = 0 ;
		static $presenters = array();
    static $custom_posts = array();
    static $custom_post_formats = array();
		static $custom_users = array();
		static $custom_classes = array();
		static $custom_singles = array();
		static $custom_taxonomies = array();
		static $restricted_menus = array();
		static $restrict_for_everyone = false;
		static $roles = array();

		static $actions = array();
		static $role_requirements = array();

		static $rewrite_rules = array();
		static $query_vars = array();
		static $permastructs = array();

		static $absent_roles = array();
		static $migrations = array();

		static $has_translations = false;

		static $scripts_version = false;
		static $styles_version = false;

		static function path($path){
			return plugin_dir_path(dirname(__FILE__)). $path;
		}

		static function url($url){
			return plugin_dir_url(dirname(__FILE__)). $url ;
		}

		static function presenter(){
			$namespace = get_namespace(get_called_class());
			$class = $namespace.'\Presenters\Base';
			if(!class_exists($class))
				$class = '\DefaultPresenter';
			return $class ;
		}

		static function build(){
			$base = get_called_class(); $namespace = '\\'.get_namespace($base) . '\\'; $prefix = strtolower(str_replace('\\', '', $namespace));
			foreach (array_merge(static::$custom_taxonomies, static::$custom_users, static::$custom_classes, static::$custom_post_formats, static::$custom_posts, static::$custom_singles) as $object) {
				require( static::path('models/'. $object . '.php'));
				$class = $namespace. ucfirst($object);
				$class::build();

			}

			if(static::$has_translations){
				require( static::path('base/Translation.php'));
				$class = $namespace.'Translation' ;
				$class::build();
			}

			foreach (static::$presenters as $presenter) {
				require(static::path('presenters/'.$presenter.'.php'));
				$class = $namespace.'Presenters\\'.ucfirst($presenter);
				$class::build();
			}
			require_once 'DefaultPresenter.php';
			\DefaultPresenter::$namespace = $namespace;
			\DefaultPresenter::build();


			add_filter('option_permalink_structure', function($structure){
				return $structure = '/%postname%/' ;
			});

			add_filter('query_vars', function($vars) use($base){
				foreach ($base::$query_vars as $var => $regex) {
					$vars[]=$var;
				}
				return $vars;
			});

			add_action('template_redirect', function() use ($base, $namespace){
				global $wp_query;

				foreach ($base::$role_requirements as $role => $urls) {
					global $wp_query ;
					$request = $_SERVER['REQUEST_URI'];
					if($request[ strlen($request)-1] == '/') $request = substr($request, 0, -1);
					foreach ($urls as $url) {
						if($url[0] != '/') $url = '/'. $url;
						if(substr($request, -strlen($url)) === $url ){
							if(!is_user_logged_in() || !current_user_can($role)){
								if(current_user_can('administrator')) continue;
								$user_class = $namespace. ucfirst($role);
								$user_class::auth();
							}
						}
					}

				}

				foreach($base::$actions as $class => $actions){
					$class =  strpos($class, 'Presenters') === false ? $namespace.'Presenters\\'.$class : $class ;
					foreach ($actions as $action => $options) {
						if(isset($options['page']))
							$options['pagename'] = $options['page'];
						if(isset($options['tax']))
							$options['taxonomy'] = $options['tax'] ;


						# is
						if(isset($options['is'])){
							if($options['is'] == 'page' && ! is_page()){ continue ; }
							if($options['is'] == 'single' && ! is_single()){ continue ; }
						}

						# method
						if(isset($options['method'])){
							$options['method'] = strtoupper($options['method']);
							if($options['method'] != strtoupper($_SERVER['REQUEST_METHOD']))
								continue;
						}
						# pagename
						if(isset($options['pagename'])){
							if(!isset($wp_query->query['pagename'])) continue;
							if($options['pagename'] != $wp_query->query['pagename'])
								continue;
						}
						# single
						if(isset($options['single'])){
							if(!isset($wp_query->query['post_type'])) continue;
							if(!is_single() && $options['single'] != $wp_query->query['post_type'])
								continue;
						}
						# archive
						if(isset($options['archive'])){
							if(!isset($wp_query->query['post_type'])) continue;
							if(!is_archive() || $options['archive'] != $wp_query->query['post_type'])
								continue;
						}

						$class::$action();
					}
				}

			});

			add_action('plugins_loaded', function() use($base, $namespace, $prefix) {

				$db_version = get_option( $prefix.'_db_version', '0');

				add_filter('init', function() use($base){
					foreach ($base::$query_vars as $var => $regex) {
						add_rewrite_tag("%$var%", $regex, "$var=" );
					}
					foreach($base::$permastructs as $name => $rule){
						add_permastruct( $name, $rule );
					}
				});

				if(!empty($base::$migrations)){
					add_action($prefix.'_update', function($version) use($base, $namespace, $prefix){
						$migrated_versions = get_option($prefix.'_migrated_versions', array());
						foreach ($base::$migrations as $version => $migrations) {
							if(!in_array($version, $migrated_versions)){
								foreach (loopable($migrations) as $migration) {
									$migration = 'migrate_'.$migration ;
									$base::$migration();
								}
								$migrated_versions[]= $version ;
								update_option($prefix.'_migrated_versions', $migrated_versions);
							}
						}
					});
				}



				if( ! is_numeric($base::$db_version) || floatval($db_version) < $base::$db_version) {
					if(! empty($base::$custom_taxonomies)) \CustomTaxonomy::build_database();
					do_action($prefix.'_update', $base::$db_version);
					foreach (array_merge($base::$custom_classes, $base::$custom_users) as $class) {
						$class = $namespace. $class ;
						if(method_exists($class, 'build_database')) $class::build_database();
					}

					if(!empty($base::$absent_roles) || false ){
						foreach ($base::$absent_roles as $role) {
							remove_role($role);
						}
					}



					add_action('wp_loaded', function() use ($base, $prefix) {
						if(has_action( "$prefix-rewrite_rules"))
							do_action("$prefix-rewrite_rules");

						global $wp_rewrite ; $wp_rewrite->flush_rules();
					});

					if(is_numeric($base::$db_version))
						update_option($prefix.'_db_version', $base::$db_version);
				}
			} );


			add_filter('rewrite_rules_array', function($rules) use($base, $prefix){
				foreach ($base::$rewrite_rules as $rule => $route) {
					$matches = 1 ;
					if($rule[sizeof($rule)-1] != '$' && $rule[sizeof($rule)-1] != '?')
						$rule = $rule.'?$' ;
					if(empty($route) || (strpos($route, 'index.php?') === false && strpos($route, '/') === false ))
						$route = 'index.php?'. $route ;
					$base::$query_vars['paged'] = 'page/([0-9]+)' ;
					foreach ($base::$query_vars as $var => $regex) {
						if(strpos($rule, "%$var%") !== false){
							$rule = str_replace("%$var%", $regex, $rule);
							if($route[strlen($route)-1] != '?')
								$route .= '&';
							$route .= sprintf('%s=$matches[%s]', $var, $matches);
							$matches++ ;
						}
					}
					$rules = array_merge(array($rule => $route), $rules);
				}
				return $rules ;
			});

			if(!empty(static::$restricted_menus)){
				$restricted_menus = static::$restricted_menus;
				add_action('admin_menu', function() use ($restricted_menus, $base){
					if( $base::$restrict_for_everyone || !current_user_can('manage_options')){
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
				$domain = strtolower(get_namespace($base));
				if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return ;
				if(!isset($_POST['post']) && !isset($_POST['post_type'])) return ;


				if( isset($_POST['post_type']) && ( in_array(ucfirst($_POST['post_type']), $base::$custom_posts) || 'translation' == $_POST['post_type']) ){
					$object = $_POST[$_POST['post_type']]; $class = $namespace. ucfirst($_POST['post_type']);
				}

				if(isset($_POST['custom_single']) && in_array($_POST['custom_single'], $base::$custom_singles) ){
					$class = $namespace.$_POST['custom_single']; $object = $_POST[$_POST['post_type']];
				}

				if(!isset($object)) $object = array() ;
				if(empty($_POST) || !isset($class)) return ;
				$array_fields = array();
				foreach ($class::$fields as $field_name => $field_options) {
					if($field_options['type'] == 'boolean' && !isset($object[$field_name])){ $object[$field_name] = 0 ; }

					if(isset($object[$field_name]) ){

						if(strpos($field_name, '-') !== false){
							list($array, $field) = explode('-', $field_name);
							if(!isset($array_fields[$array])) $array_fields[$array] = array();
							$array_fields[$array][$field] = $object[$field_name];
							continue;
						}

						update_post_meta($post_id, $field_name, $object[$field_name]) ;
					} elseif ($class::$fields[$field_name]['type'] == 'boolean') {
						update_post_meta($post_id, $field_name, 0);
					}
				}

				foreach($array_fields as $field_name => $values){
					update_post_meta($post_id, $field_name, $values);
				}


				if(get_post_meta($post_id, '_notnew', true) != ''){
					$new = false;
				} else {
					update_post_meta($post_id, '_notnew', true);
					$new = true;
				}
				$object['_new'] = $new;

				do_action(sprintf('%s-%s-save', $domain, $class::$name), $post_id, $object);

			});

			add_action('admin_enqueue_scripts', function() use ($base, $namespace) {
				wp_enqueue_style(__NAMESPACE__.'-admin', $base::url('css/admin/main.css') );
				wp_enqueue_script(__NAMESPACE__.'-admin', $base::url('js/admin/main.js') );
				$screen = get_current_screen() ;
				if( $screen->base == 'edit-tags' && in_array(ucfirst($screen->taxonomy), $base::$custom_taxonomies )){
					$name = $screen->taxonomy ;
				} elseif (in_array(ucfirst($screen->post_type), $base::$custom_posts)) {
					$name = $screen->post_type;
				} elseif($screen->base == 'profile' || $screen->base == 'user-edit') {
					global $profileuser;

					foreach ($base::$custom_users as $user) {
						$model = $namespace.$user;
						if(in_array(strtolower($model::$name), $profileuser->roles) ||
							(
								$model::$allow_admin == true &&
								in_array('administrator', $profileuser->roles)
							)
						){
							$name = strtolower($model::$name);
						}
					}
				}
				if(isset($name)){
					wp_enqueue_script( $name, $base::url( "js/admin/$name.js") );
					wp_enqueue_style( $name, $base::url( "css/admin/$name.css") );
					$class = $namespace.ucfirst($name);

					foreach ($class::$fields as $field => $options) {
						if(!isset($options['type'])) continue;
						if( 'date' == $options['type'] ){
							wp_enqueue_script('jquery-datepick', $base::url('lib/js/jquery-datepick/jquery.datepick.js'), array('jquery'));
							if(WPLANG == 'pt_BR')
								wp_enqueue_script('jquery-datepick-br', $base::url('lib/js/jquery-datepick/jquery.datepick-pt-BR.js'), array('jquery-datepick'));
							wp_enqueue_style('jquery-datepick', $base::url('lib/js/jquery-datepick/smoothness.datepick.css'));
							wp_enqueue_script('datepicker', $base::url('lib/js/utils/datepicker.js'), array('jquery-datepick-br'));
						}

						if ('file' == $options['type']) {
							wp_enqueue_script('file_upload', $base::url('lib/js/utils/file_upload.js'), array('jquery'));
						}

						if ('media' == $options['type']) {
							wp_enqueue_media();
							wp_enqueue_script('media_upload', $base::url('lib/js/utils/media_upload.js'), array('jquery'));
						}

						if ('multiple' == $options['type']) {
							wp_enqueue_script('multiple', $base::url('lib/js/utils/multiple.js'), array('jquery'));
						}

						if( 'geo' == $options['type']){
							wp_enqueue_script('jquery-ui-autocomplete', array('jquery'));
							wp_enqueue_script('gmaps-api', 'http://maps.google.com/maps/api/js?sensor=false&language=pt-BR', array('jquery')) ;
							wp_enqueue_script('geo-field', $base::url('lib/js/utils/geo-field.js'), array('jquery', 'gmaps-api'));
						}
					}

					if(isset($class::$tabs)){
						wp_enqueue_script('custom_post_tabs', $base::url('lib/js/utils/tabs.js'), array('jquery'));
						if(isset($class::$formats)){
							wp_enqueue_script('custom_post_formats', $base::url('lib/js/utils/post_formats.js'), array('jquery'));
						}
					}

					if(isset($class::$hide_custom_fields) && $class::$hide_custom_fields){
						add_action('admin_print_scripts', function(){ ?>
							<script>
							jQuery(function($){
								$('#postcustom').hide();
							});
							</script>
						<?php }, 99);
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
				<?php } return $args ;
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
