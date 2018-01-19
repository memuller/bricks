<?php
namespace Bricks;
class CustomPost extends BaseItem {

  static  $content_type = 'post';
  static  $public = true,
          $labels, $supports, $taxonomies,
          $description, $show_ui, $menu_position, $menu_icon,
          $hierarchical = false, $show_in_rest = true, $capability_type, $capabilities, $map_meta_cap, $hide_add = false ;


  static function prepare_parameters(){
    $klass = get_called_class(); $params = array();
    # sets post registration parameters
    foreach(['label', 'labels', 'public', 'supports', 'taxonomies', 'description', 'show_ui', 'menu_position', 'menu_icon', 'hierarchical', 'capability_type', 'show_in_rest', 'capabilities', 'map_meta_cap'] as $arg){
      if(isset($klass::$$arg)){
        $params[$arg] = $klass::$$arg;
      }
    }
    $klass::$creation_parameters =& $params;
  }


  static function create_content_type(){
    $klass = get_called_class();
    add_action('init', function() use($klass) {
      \register_post_type( $klass::name(), $klass::$creation_parameters );
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
    if('status' == $thing) $thing = 'post_status';
    if('id' == $thing) $thing = 'ID';
    # returns a custom field or a post attribute
    return parent::__get($thing);
  }

  function __set($thing, $value){
    if(in_array($thing, ['title', 'name', 'content'])){
      $thing = "post_$thing";
    }

    return parent::__set($thing, $value);
  }

  static function all($params = array()){
    $class = get_called_class();
    $default_params = [
      'post_type'     => static::name()
    ];

    $params = array_merge($default_params, $params);
    $posts = get_posts($params);
    return array_map(function($post) use($class){
      return new $class($post);
    }, $posts);
  }

  static function setup_hooks(){
    $class = get_called_class();

    if (is_callable([$class, 'on_views_edit'])){
      add_filter("views_edit-".static::name(), [$class, 'on_views_edit']);
    }
    
    if (is_callable([$class, 'on_save'])){
      add_action('save_post', function($post_id, $post, $update) use ($class) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return ;
        if ($class::name() != $post->post_type) return ;
        $obj = new $class($post_id);
        $class::on_save($obj, $update);
      }, 10, 3);
    }
  }

}

?>
