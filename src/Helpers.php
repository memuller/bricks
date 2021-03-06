<?php

function from_camel_case(string $str) {
  $str[0] = strtolower($str[0]);
  $func = create_function('$c', 'return "_" . strtolower($c[1]);');
  return preg_replace_callback('/([A-Z])/', $func, $str);
}

function to_camel_case(string $str, bool $capitalise_first_char = true) {
  if($capitalise_first_char) {
    $str[0] = strtoupper($str[0]);
  }
  $func = create_function('$c', 'return strtoupper($c[1]);');
  return preg_replace_callback('/_([a-z])/', $func, $str);
}

function pluralize (string $word) {
  if ($word[sizeof($word)-1] != 's') $word .= 's';
  return $word;
}

function singularize (string $word) {
  if ($word[sizeof($word)-1] == 's') $word = substr($word, 0, -1);
  return $word;
}

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

/**
* * returns a given class's name; without namespaces.
*
* @param string $class a valid class path
* @return string the class name
*/
function get_classname(string $arg){
  $class = explode('\\', $arg);
  return $class[sizeof($class)-1];
}

function sibling_class(string $class, string $sibling){
  $namespace = get_namespace($sibling);
  return $namespace.'\\'.$class ;
}

function model_for(string $post_type) {
  $class_name = to_camel_case($post_type);
  $class = implode('\\', [BRICKS_NAMESPACE, $class_name]);
  if (!class_exists($class)) return null;
  if ($class::name() !== $post_type) return null;
  return $class;
}

function loopable($arg){
  if ($arg == null) return [];
  return is_array($arg) ? $arg : [$arg];
}

function recursive_replace($arr, $values) {
  foreach ($arr as $key => $value) {
    if (is_array($value)){
      $arr[$key] = recursive_replace($value, $values);
    } else {
      foreach($values as $user_key => $user_value) {
        if (strpos($value, '$'.$user_key) !== false) {
          $arr[$key] = str_replace('$'.$user_key, $user_value, $value);
        }
      }
    }
  }
  return $arr;
}

function maybe_decode ($data, $assoc = true) {
  $unserialized = json_decode($data, $assoc);
  return $unserialized !== null ? $unserialized : $data;
}
