<?php 
namespace Bricks;
class CustomPost extends BaseItem {
  
  static  $content_type = 'post';
  static  $public = true,
          $labels, $supports, $taxonomies,
          $description, $show_ui, $menu_position, $menu_icon,
          $hierarchical = false, $show_in_rest = true, $capability_type ;

  
  static function prepare_parameters(){
    
    # sets post registration parameters
    foreach(['label', 'labels', 'public', 'supports', 'taxonomies', 'description', 'show_ui', 'menu_position', 'menu_icon', 'hierarchical', 'capability_type', 'show_in_rest'] as $arg){
      if(isset(static::$$arg)){
        static::$creation_parameters[$arg] = static::$$arg;
      }
    }
  }

  
  static function create_content_type(){
    $klass = get_called_class();
    add_action('init', function() use($klass) {
      \register_post_type( $klass::$name, $klass::$creation_parameters );
    });
  }

  function __construct($arg=false){
    if(!$arg){
      $this->base = $GLOBALS['post'];
    } elseif(is_numeric($arg)){
      $this->base = get_post($arg);
    } else {
      $this->base = $arg;
    }
    parent::__construct($this->base);
  }

  function __get($thing){
    # alias for returning post permalink
    if('permalink' == $thing) return get_permalink($this->base->ID);
    # aliases for some post values
    if('title' == $thing) $thing = 'post_title';
    if('content' == $thing) $thing = 'post_content';
    if('id' == $thing) $thing = 'ID';
    # returns a custom field or a post attribute
    return parent::__get($thing);
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