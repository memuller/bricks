<?php
  require_once 'src/BaseItem.php';
  require_once 'src/CustomUser.php';
  require_once 'src/CustomPost.php' ;
  require_once 'src/CustomSingle.php';


  function property_or_key($object, string $arg){
		return is_array($object) ? $object[$arg] : $object->$arg ;
	}

  function property_or_method($object, string $arg) {
    return method_exists($object, $arg) ? $object->{$arg}() : $object->$arg; 
  }

	function get_namespace(string $class){
		$namespace = explode('\\', $class);
		return $namespace[0];
	}

	function get_classname(string $arg){
		$class = explode('\\', $arg);
		return $class[sizeof($class)-1];
	}

	function sibling_class(string $class, string $sibling){
		$namespace = get_namespace($sibling);
		return $namespace.'\\'.$class ;
	}

	function loopable($arg){
		return is_array($arg) ? $arg : [$arg];
	}


	spl_autoload_register(function(string $class){
    $class_path = explode('\\', $class);
    $class_name = $class_path[sizeof($class_path)-1];
    $namespace = $class_path[0];
    if(BRICKS_NAMESPACE == $namespace){
			$path = implode(DIRECTORY_SEPARATOR, [BRICKS_BASE_DIR, 'models', "$class_name.php"]);
			if (!file_exists($path)) return;
      require_once $path;
    }
	});
	
  foreach(glob(implode(DIRECTORY_SEPARATOR, [BRICKS_BASE_DIR, 'models', '*.php'])) as $file){
    $path = explode(DIRECTORY_SEPARATOR, $file);
    $class_name =  explode('.', $path[sizeof($path)-1])[0] ;
    $class = BRICKS_NAMESPACE.'\\'.$class_name;
    $class::init();
  }
	

?>
