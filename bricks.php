<?php
  require_once 'src/BaseItem.php';
  require_once 'src/CustomUser.php';
  require_once 'src/CustomPost.php' ;


  function property_or_key($object, $arg){
		return is_array($object) ? $object[$arg] : $object->$arg ;

	}

	function get_namespace($class){
		$namespace = explode('\\', $class);
		return $namespace[0];
	}

	function sibling_class($class, $sibling){
		$namespace = get_namespace($sibling);
		return $namespace.'\\'.$class ;
  }
  
?>