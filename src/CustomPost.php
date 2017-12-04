<?php 
namespace Bricks;
class CustomPost {
  static  $fields = array(),
          $boxes = array();
  static  $name;
  static  $creation_parameters = array();
  static  $public = true,
          $label, $labels, $supports, $taxonomies,
          $description, $show_ui, $menu_position, $menu_icon,
          $hierarchical, $capability_type ;

  static function build(){
    $klass = get_called_class();
    $klass::prepare_post_type_parameters();
    $klass::create_post_type();
  }

  static function prepare_post_type_parameters(){
    $klass = get_called_class();

    $names = explode('\\', $klass); $name = $names[sizeof($names)-1];
    # post_type name is class name to lowercase if not set
    if(!isset($klass::$name)){
      $klass::$name = strtolower($name);
    }
    # label is the class name +'s' if not set 
    if(!isset($klass::$label) && !isset($klass::$labels)){
      $klass::$label = $name.'s';
    }
    
    # 
    foreach(['label', 'labels', 'public', 'supports', 'taxonomies', 'description', 'show_ui', 'menu_position', 'menu_icon', 'hierarchical', 'capability_type'] as $arg){
      if(isset($klass::$$arg)){
        $klass::$creation_parameters[$arg] = $klass::$$arg;
      }
    }
  }
  static function create_post_type(){
    $klass = get_called_class();
    add_action('init', function() use($klass) {
      \register_post_type( $klass::$name, $klass::$creation_parameters );
    });
  }

  static function create_metaboxes(){
    
  }
}

?>