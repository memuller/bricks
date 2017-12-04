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

  private $base_fields;
  public $base;

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
    if(!isset(static::$name)){
      static::$name = strtolower($name);
    }
    # label is the class name +'s' if not set 
    if(!isset(static::$label) && !isset(static::$labels)){
      static::$label = $name.'s';
    }
    
    # sets post registration parameters
    foreach(['label', 'labels', 'public', 'supports', 'taxonomies', 'description', 'show_ui', 'menu_position', 'menu_icon', 'hierarchical', 'capability_type'] as $arg){
      if(isset(static::$$arg)){
        static::$creation_parameters[$arg] = static::$$arg;
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
    foreach(static::$boxes as $bid => $box){
      # sets up box parameters
      $field_names = $box['fields'];
      $box_parameters = array_diff_key($box, ['fields']);
      $box_parameters['id'] = $bid;
      $box_parameters['pages'] = [static::$name];
      $field_parameters = [];
      # add parameters for each field
      foreach($field_names as $field_name){
        $parameters = static::$fields[$field_name];
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

  static function has_field($field){
    return isset(static::$fields[$field]);
  }

  function __construct($arg=false){
    if(!$arg){
      $this->base = $GLOBALS['post'];
    } elseif(is_numeric($arg)){
      $this->base = get_post($arg);
    } else {
      $this->base = $arg;
    }
    
    $all_meta = get_post_custom($this->base->ID);
    foreach($all_meta as $field_name => $field_values){
      if(static::has_field($field_name)){
        $this->base_fields[$field_name] = $field_values[0];
      }
    }
  }

  function __get($thing){
    # alias for returning post permalink
    if('permalink' == $thing) return get_permalink($this->base->ID);
    # aliases for some post values
    if('title' == $thing) $thing = 'post_title';
    if('content' == $thing) $thing = 'post_content';
    if('id' == $thing) $thing = 'ID';
    # returns a custom field or a post attribute
    if(static::has_field($thing)){
      return $this->base_fields[$thing];
    } elseif(property_exists($this->base, $thing)){
      return $this->base->{$thing};
    }
  }

  function __set($thing, $value){
    if(in_array($thing, ['title', 'name', 'content'])){
      $thing = "post_$thing";
    }
    if(property_exists($this->base, $thing)){
      $this->base->{$thing} = $value;
      \wp_update_post($this->base);
    } else {
      \update_metadata('post', $this->base->ID, $thing, $value);
    }
    return $value;
  }

}

?>