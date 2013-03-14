<?php 
	
	class Presenter {

		static $uses = array();
		static $ajax_actions = array();
		static $actions = array();
		static $includes = array();
		static $scripts = array();
		static $styles = array();

		static function render_to_string($view, $scope=array()){
			global $plugin_haml_parser ; 
			$path = get_called_class(); $path = explode('\\', $path); $path = $path[0];
			$path = "\\".$path.'\Plugin' ; $path = $path::path('views'.DIRECTORY_SEPARATOR);
			$file = get_theme_root() . DIRECTORY_SEPARATOR . get_stylesheet() . DIRECTORY_SEPARATOR . 'views'. DIRECTORY_SEPARATOR. $view . '.php' ;
			if( ! file_exists($file))
				$file = $path . $view . '.php' ;
			
			if(file_exists($file)){
				$scope['presenter'] = get_called_class();
				extract($scope) ;
				ob_start() ;
				require $file ;
				$view = ob_get_contents() ;
				ob_end_clean() ;
				return $view ;
			}

			if( ! isset($plugin_haml_parser)) $plugin_haml_parser = new HamlParser($path, $path);
			
			if ( ! empty($scope)) $plugin_haml_parser->append($scope);
			
			return $plugin_haml_parser->fetch($view . '.haml') ;
		}

		static function render ($view, $scope=array()){
			echo static::render_to_string($view, $scope) ;
		}

		static function render_partial($partial, $scope=array()){
			$exploded_path = explode('/',$partial) ;
			$exploded_path[sizeof($exploded_path)-1] = "_".$exploded_path[sizeof($exploded_path)-1] ;
			$partial = implode('/', $exploded_path) ;
			echo static::render_to_string($partial, $scope) ;
		}
		static function render_admin($view, $scope=array()){
			echo static::render_to_string('admin/'. $view, $scope) ;
		}

		static function admin_styles(){}
		static function admin_scripts(){}
		static function styles(){}
		static function scripts(){}
		static function build(){
			$class = get_called_class(); $namespace = get_namespace($class); 
			$name = explode('\\', $class) ; $name = $name[sizeof($name)-1] ;
			$base = $namespace . '\Plugin';
			
			# Loads scripts.
			foreach ($class::$uses as $resource) {
				if(strstr($resource, 'admin')){
					add_action('admin_enqueue_scripts', "$class::$resource");
				} else {
					add_action('wp_enqueue_scripts', "$class::$resource" );
				}
			}
			if(!empty($class::$scripts) || !empty($class::$styles)){
				add_action('init', function() use($class){
					foreach (array('scripts', 'styles') as $resource) {
						foreach ($class::$$resource as $name => $options) {
							$default_args = array('dependencies' => array('jquery'), 'version' => false, 'in_footer' => false);
							if('/' == $options['source'][0]){
								if(isset($options['from']) &&  'plugin' == $options['from']){
									$options['source'] = $class::url($options['source']);
								} else {
									$options['source'] = get_stylesheet_directory_uri().$options['source'];
								}
							}
							$options = array_merge($default_args, $options);
							$args = array(
								$name, $options['source'], $options['dependencies'], 
								$options['version'], $options['in_footer']);
							$function = 'scripts' == $resource ? 'wp_register_script' : 'wp_register_style' ;
							call_user_func_array($function, $args);
							
						}				
					}
				});
			}
			if(!empty($class::$includes)){
				if(is_admin()){
					add_action('admin_enqueue_scripts', function() use($class) {
						$class::enqueue_scripts();
					});
				} else {
					add_action('login_enqueue_scripts', function() use($class){
						$class::enqueue_scripts();
					});						
					add_action('wp_enqueue_scripts', function() use($class) {		
						$class::enqueue_scripts();
					});
									
				}
				
			}
			# Loads ajax actions.
			$prefix =  strtolower($namespace).'-'.strtolower($name).'-';
			foreach ($class::$ajax_actions as $action => $logged) {
				switch ($logged) {
					case 'both':
						$triggers = array('wp_ajax_', 'wp_ajax_nopriv_') ;
					break;
					case 'logged':
						$triggers = array('wp_ajax_') ;
					break;				
					default:
						$triggers = array('wp_ajax_nopriv_') ;
					break;
				}
				foreach ($triggers as $ajax) {
					add_action($ajax.$prefix.$action, "\\$class::$action");
				}
			}
			global $shit ; $shit = array();
			# Loads actions.
			if(!empty($class::$actions)){
				$base::$actions = array_merge($base::$actions, array($name => $class::$actions));
					
			}

			
		}

		static function url($arg){
			$class = get_called_class(); $base = get_namespace($class) . '\Plugin';
			return $base::url($arg);
		}

		static function render_404(){
			status_header( 404 );
			nocache_headers();
			include(get_404_template());
			exit;
		}

		static function recursive_enqueue($type, $name, $kind='main'){
			$function = $type == 'script' ? 'wp_enqueue_script' : 'wp_enqueue_style';	
			$function($name); 
			$pluralized = $type.'s'; $list = static::$$pluralized ;
			if(!empty($list[$name]['dependencies'])){
				foreach ($list[$name]['dependencies'] as $dep) {
					if(!wp_script_is($dep,'queue')) static::recursive_enqueue($type, $dep, $kind);
				}
			}	
			
		}

		static function enqueue_scripts(){
			global $wp_query;
			foreach(static::$includes as $resource){
				$condition = array_keys($resource)[0]; $value = $resource[$condition];
				$valid = true; $kind = 'main';
				switch ($condition) {
					case 'page':
						if(!is_page($value)) $valid = false;
					break;
					
					case 'single':
						if(!is_single()){ $valid = false; break; }
						if('any' != $value && ! $value == $wp_query->query['post_type']) $valid = false;
					break;

					case 'archive':
						if(!is_archive()){ $valid = false; break; }
						if('any' != $value && ! $value == $wp_query->query['post_type']) $valid = false;
					break;

					case 'taxonomy':
						if(!is_tax($value)) $valid = false;
					break;

					case 'is':
						if('single' == $value && !is_single()){ $valid = false; break; }
						if('archive' == $value && !is_archive()){ $valid = false; break; }
						if('home' == $value && !is_home()){ $valid = false; break; }
						if('search' == $value && !is_search()){ $valid = false; break; }
						if('taxonomy' == $value && !is_tax()){ $valid = false; break; }
						if('tag' == $value && !is_tag()){ $valid = false; break; }
						if('category' == $value && !is_category()){ $valid = false; break; }
						if('login' == $value){
							if( strncmp($_SERVER['REQUEST_URI'], '/wp-login.php', strlen('/wp-login.php')) ){
								$kind = 'login'; 
							} else { $valid = false ; break; }
						}
					break;

				}
				if(!$valid) continue;
				foreach (array('script', 'style') as $type) {
					$list = $type.'s';
					if(isset($resource[$list])){
						foreach ($resource[$list] as $asset) {
							static::recursive_enqueue($type, $asset, $kind);
						}
					}
				}
			}
		}
	}

	function wp_enqueue_login_script($script){
		$path = $GLOBAL['wp_scripts']->registered[$script]['src'];
		add_action('login_enqueue_scripts', function(){
			print("<script id='$script' src='$path' />");
		});
	}

	function wp_enqueue_login_style($script){
		$path = $GLOBAL['wp_scripts']->registered[$script]['src'];
		add_action( 'login_enqueue_scripts', function(){
			print("<link rel='stylesheet' id='$script'  href='$src' type='text/css' media='all' />");
		});
	}


	function html_attributes($args){
		$kv_pairs = "" ;
		foreach ($args as $name => $value) {
			$kv_pairs .= sprintf(" %s=\"%s\" ", $name, $value) ;
		}
		echo $kv_pairs ;
	}

	function description($text, $classes=''){
		printf("<span style='display:block;' class='description $classes'>%s</span>", $text);
	}

	function label($label, $for, $classes=null){
		printf( "<label %sfor='%s'>%s</label>", (null == $classes ? '' : "class=\"$classes\" " ),  $for, $label ) ;
	}

	function hidden_field($name, $value){
		printf("<input type='hidden' name='%s' value='%s' >", $name, $value);	
	}

	function flash($arg){
		global $flash; 
		$flash = $arg ; 
	}

	function display_flash_messages($arg=null){
		global $flash ; 
		if($arg) $flash = $arg;
		if(isset($flash)){
			if(!is_array($flash)) $flash = array('type' => 'info', 'text' => $flash); ?>
			<div id="message" class="<?php echo 'bellow-h2 '. ($flash['type'] == 'error' ? 'error' : 'updated') ?>">
				<p><?php echo $flash['text'] ?></p>
			</div>
		<?php }
	}

	
	function property_or_key($object, $arg){
		return is_array($object) ? $object[$arg] : $object->$arg ;
			
	}

	function get_namespace($class){
		$namespace = explode('\\', $class);
		return $namespace[0];
	}

	function debug($arg, $name=''){ 
		if(function_exists('dbgx_trace_var' || false)){
			if('' == $name) $name = false ;
			dbgx_trace_var($arg, $name);
		} else {
			if(! is_string($arg))
				$arg = print_r($arg, true); 
			trigger_error($name.':'.$arg, E_USER_WARNING);
		}
	}

	function loopable($arg){
		return !is_array($arg) ? array($arg) : $arg ;
	}
	/**
 * Download an image from the specified URL and attach it to a post.
 * Modified version of core function media_sideload_image() in /wp-admin/includes/media.php  (which returns an html img tag instead of attachment ID)
 * Additional functionality: ability override actual filename, and to pass $post_data to override values in wp_insert_attachment (original only allowed $desc)
 *
 * @since 1.4 Somatic Framework
 *
 * @param string $url (required) The URL of the image to download
 * @param int $post_id (required) The post ID the media is to be associated with
 * @param bool $thumb (optional) Whether to make this attachment the Featured Image for the post (post_thumbnail)
 * @param string $filename (optional) Replacement filename for the URL filename (do not include extension)
 * @param array $post_data (optional) Array of key => values for wp_posts table (ex: 'post_title' => 'foobar', 'post_status' => 'draft')
 * @return int|object The ID of the attachment or a WP_Error on failure
 */
function somatic_attach_external_image( $url = null, $post_id = null, $thumb = null, $filename = null, $post_data = array() ) {
    if ( !$url || !$post_id ) return new WP_Error('missing', "Need a valid URL and post ID...");
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    // Download file to temp location, returns full server path to temp file, ex; /home/user/public_html/mysite/wp-content/26192277_640.tmp
    $tmp = download_url( $url );

    // If error storing temporarily, unlink
    if ( is_wp_error( $tmp ) ) {
        @unlink($file_array['tmp_name']);   // clean up
        $file_array['tmp_name'] = '';
        return $tmp; // output wp_error
    }

    preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);    // fix file filename for query strings
    $url_filename = basename($matches[0]);                                                  // extract filename from url for title
    $url_type = array('ext' => 'jpg');                                           // determine file type (ext and mime/type)

    // override filename if given, reconstruct server path
    if ( !empty( $filename ) ) {
        $filename = sanitize_file_name($filename);
        $tmppath = pathinfo( $tmp );                                                        // extract path parts
        $new = $tmppath['dirname'] . "/". $filename . "." . $tmppath['extension'];          // build new path
        rename($tmp, $new);                                                                 // renames temp file on server
        $tmp = $new;                                                                        // push new filename (in path) to be used in file array later
    }

    // assemble file data (should be built like $_FILES since wp_handle_sideload() will be using)
    $file_array['tmp_name'] = $tmp;                                                         // full server path to temp file

    if ( !empty( $filename ) ) {
        $file_array['name'] = $filename . "." . $url_type['ext'];                           // user given filename for title, add original URL extension
    } else {
        $file_array['name'] = $url_filename;                                                // just use original URL filename
    }

    // set additional wp_posts columns
    if ( empty( $post_data['post_title'] ) ) {
        $post_data['post_title'] = basename($url_filename, "." . $url_type['ext']);         // just use the original filename (no extension)
    }

    // make sure gets tied to parent
    if ( empty( $post_data['post_parent'] ) ) {
        $post_data['post_parent'] = $post_id;
    }

    // required libraries for media_handle_sideload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // do the validation and storage stuff
    $att_id = media_handle_sideload( $file_array, $post_id, null, $post_data );             // $post_data can override the items saved to wp_posts table, like post_mime_type, guid, post_parent, post_title, post_content, post_status

    // If error storing permanently, unlink
    if ( is_wp_error($att_id) ) {
        @unlink($file_array['tmp_name']);   // clean up
        return $att_id; // output wp_error
    }

    // set as post thumbnail if desired
    if ($thumb) {
        set_post_thumbnail($post_id, $att_id);
    }

    return $att_id;
}

	function GCD($a, $b) {  
		while ($b != 0){ 
			$remainder = $a % $b;  
			$a = $b;  
			$b = $remainder;  
    	}  
		return abs ($a);  
    }

 ?>