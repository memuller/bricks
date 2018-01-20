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
?>
