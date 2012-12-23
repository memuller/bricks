<?php 
	
	class Presenter {

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
			$class = get_called_class();
			foreach ($class::$uses as $resource) {
				if(strstr($resource, 'admin')){
					add_action('admin_enqueue_scripts', "$class::$resource");
				} else {
					add_action('wp_enqueue_scripts', "$class::$resource" );
				}
			}
		}

		static function url($arg){
			$class = get_called_class(); $base = get_namespace($class) . '\Plugin';
			return $base::url($arg);
		}
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

 ?>