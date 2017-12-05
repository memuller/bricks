<?php
namespace Bricks;
class BaseItem {
  static  $fields = array(),
          $boxes = array(),
          $columns = array(),
          $name, $label,
          $creation_parameters = array();
  
  public $base, $base_fields;

  static function build(){
    $klass = get_called_class();
    static::guess_names();
    static::prepare_parameters();
    static::create_content_type();
    static::create_metaboxes();
    static::set_columns();
  }

  static function guess_names(){
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
  }

  static function create_metaboxes(){
    $klass = get_called_class();
    foreach(static::$boxes as $bid => $box){
      # sets up box parameters
      $field_names = $box['fields'];
      $box_parameters = $box;
      unset($box_parameters['fields']);
      $box_parameters['id'] = $bid;
      $box_parameters['object_types'] = static::$content_type == 'post' ? [static::$name] : [static::$content_type] ;
      // $box_parameters['show_on_cb'] = function() use($klass){
      //   return false;
      // };
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
      # hooks box creation
      add_action('cmb2_admin_init', function() use($box_parameters, $field_parameters){
        $box = new_cmb2_box($box_parameters);
        foreach($field_parameters as $field){
          $box->add_field($field);
        }
      });
    }
  }

  static function set_columns(){
    $klass = get_called_class(); 
    $class_name = static::$name;
    $has = [ 'add' => !empty(static::$columns) ];
    $filters = [
      'set' => "manage_${class_name}_posts_columns",
      'display' => "manage_${class_name}_posts_custom_column"
    ];

    if($has['add']){
      
      add_filter($filters['set'], function($columns) use($klass, $has) {
        if($has['add']){
          $columns_to_add = $klass::$columns;
          if(isset($columns['date'])){
            unset($columns['date']);
            $columns_to_add['date'] = __('Date');
          }
          foreach($columns_to_add as $name => $label){
            $columns[$name] = __($label);
          }
        }
        return $columns;
      });

      add_action($filters['display'], function($column, $ID) use($klass){
        $obj = new $klass($ID);
        if($klass::has_field($column) || property_exists($obj->base, $column)){
          echo $obj->{$column};
        }
      }, 10, 2);
    }

  }

  static function has_field($field){
    return isset(static::$fields[$field]);
  }

  function __construct($arg=false){
    $all_meta = get_metadata(static::$content_type, $this->base->ID);
    foreach($all_meta as $field_name => $field_values){
      if(static::has_field($field_name)){
        $this->base_fields[$field_name] = $field_values[0];
      }
    }
  }
}
?>