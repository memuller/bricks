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
    $klass::prepare_parameters();
    $klass::create_post_type();
    $klass::create_metaboxes();
  }

  static function prepare_parameters(){
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
    
    # sets post registration parameters
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
    $klass = get_called_class();
    foreach($klass::$boxes as $bid => $box){
      # sets up box parameters
      $field_names = $box['fields'];
      $box_parameters = array_diff_key($box, ['fields']);
      $box_parameters['id'] = $bid;
      $box_parameters['pages'] = [$klass::$name];
      $field_parameters = [];
      # add parameters for each field
      foreach($field_names as $field_name){
        $parameters = $klass::$fields[$field_name];
        $parameters['id'] = $field_name;
        if(!isset($parameters['type'])){
          $parameters['type'] = 'text';
        }
        $field_parameters[]= $parameters;
      }
      $box_parameters['fields'] = $field_parameters;
      
      # hooks box creation
      add_action('cmb_meta_boxes', function(array $boxes) use($box_parameters){
        $boxes[]= $box_parameters;
        return $boxes;
      });
    }
  }
}

?>