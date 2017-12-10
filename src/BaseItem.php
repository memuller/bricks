<?php
namespace Bricks;
class BaseItem {
  static  $fields = array(),
          $boxes = array(),
          $columns = array(),
          $name, $label, $creation_parameters,
          $has_one = false, $has_many = false, $belongs_to = false;
  
  public $base, $base_fields;

  static function build(){
    $klass = get_called_class();
    static::prepare_parameters();
    static::prepare_relationships();
    static::create_content_type();
    static::create_metaboxes();
    static::set_columns();
  }

  static function label(){
    if(isset(static::$label)) return static::$label;
    $name = static::name();
    return ucfirst($name).'s';
  }

  static function name(){
    if(isset(static::$name)) return static::$name; 
    $klass = get_called_class();
    $names = explode('\\', $klass); $name = $names[sizeof($names)-1];
    return strtolower($name);
  }

  static function prepare_relationships(){
    if(!static::$belongs_to) return ;
    $class = get_called_class(); $namespace = get_namespace($class);
    $boxes = static::$boxes; $fields = static::$fields;

    foreach(loopable(static::$belongs_to) as $parent){
      $parent_class = sibling_class(ucfirst($parent), $class);
      $fields[$parent] = [
        'id'                => $parent_class::name(),
        'name'              => $parent_class::label(),
        'type'              => 'select',
        'show_option_none'  => false,
        'options_cb'        => function() use ($parent_class){
          $posts = $parent_class::all();
          $options = array();
          foreach($posts as $post){
            $options[$post->ID] = $post->title;
          }
          return $options;
        }
      ];
    }

    static::$fields =& $fields;
  }

  static function create_metaboxes(){
    $klass = get_called_class(); $name = static::name();
    foreach(static::$boxes as $bid => $box){
      # sets up box parameters
      $field_names = $box['fields'];
      $box_parameters = $box;
      unset($box_parameters['fields']);
      $box_parameters['id'] = "{$name}_$bid";
      $box_parameters['object_types'] = static::$content_type == 'post' ? [$name] : [static::$content_type] ;
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
    $class_name = static::name();
    
    $has = [ 'add' => !empty($klass::$columns) ];
    
    if('post' == static::$content_type){
      $filters = [
        'set' => "manage_${class_name}_posts_columns",
        'display' => "manage_${class_name}_posts_custom_column"
      ];
    } else {
      $filters = [
        'set' => "manage_users_columns",
        'display' => 'manage_users_custom_column'
      ];
    }

    if($has['add']){
      add_filter($filters['set'], function($columns) use($klass, $has) {
        # skips column if we're on a user page that isn't listing this class user role
        if($klass::$content_type == 'user' && !(isset($_GET['role'])) || $_GET['role'] != $klass::name()){
          return $columns;
        }

        $columns_to_add = $klass::$columns;
        # if there's a date column, moves it so it's always the last one
        if(isset($columns['date'])){
          unset($columns['date']);
          $columns_to_add['date'] = __('Date');
        }

        foreach($columns_to_add as $name => $label){
          $columns[$name] = __($label);
        }
        return $columns;
      });

      if('post' == static::$content_type){
        add_action($filters['display'], function($column, $ID) use($klass){
          $obj = new $klass($ID);
          echo $obj->{$column} ? $obj->{$column} : '—' ;
        }, 10, 2);
      } else {
        add_filter($filters['display'], function($out, $column, $ID) use($klass){
          $obj = new $klass($ID);
          $out = $obj->{$column};
          return $out ? $out : '—';
        }, 10, 3);
      }
    }

  }

  static function has_field($field){
    return isset(static::$fields[$field]);
  }

  function save_base(){
    if('post' == static::$content_type){
      return \wp_update_post($this->base);
    } else {
      return \wp_update_user($this->base);
    }
  }

  function __construct($arg=false){
    $all_meta = get_metadata(static::$content_type, $this->base->ID);
    foreach($all_meta as $field_name => $field_values){
      if(static::has_field($field_name)){
        $this->base_fields[$field_name] = $field_values[0];
      }
    }
  }

  function __get($thing){
    if(static::has_field($thing)){
      return $this->base_fields[$thing];
    } elseif(property_exists($this->base, $thing)){
      return $this->base->{$thing};
    }
  }

  function __set($thing, $value){
    if(property_exists($this->base, $thing)){
      $this->base->{$thing} = $value;
      $this->save_base();  
    } else {
      \update_metadata(static::$content_type, $this->base->ID, $thing, $value);
    }
    return $value;
  }
}
?>