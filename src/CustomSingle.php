<?php
namespace Bricks;
trait CustomSingle  {
  
  static function get_ID() {
    $posts = get_posts([ 'name' => static::$slug, 'post_type' => static::$post_type]);
    if (sizeof($posts) > 0) {
      return $posts[0]->ID ;
    } else {
      return false;
    }
  }

  static function get () {
    $id = static::get_ID();
    if (!$id) return null;
    return new static($id);
  }

  static function create_content_type() {
    $class = get_called_class();
    add_action('admin_init', function() use($class) {
      if ($class::get_ID() === false) {
        $class::create_single();
      }
    });
  }

  static function create_single() {
    $id = wp_insert_post([
      'post_type' => static::$post_type,
      'post_status' => 'publish',
      'post_name' => static::$slug,
      'post_title' => static::$title
    ]);
    $obj = new static($id);
    if (static::$fields && sizeof(static::$fields) > 0){
      foreach (static::$fields as $field => $option) {
        $obj->{$field} = $option['default'];
      }
    }
  }

  static function name() {
    return static::$post_type;
  }

  static function prepare_metaboxes() {
    parent::prepare_metaboxes();
    if (!static::$boxes || sizeof(static::$boxes) == 0) return ;
    $class = get_called_class();
    $boxes = static::$boxes ;
    foreach ($boxes as $name => $options) {
      $options['show_on_cb'] = function($box) use ($class) {
        $id = $class::get_ID();
        return $id == $box->object_id();
      };
      $boxes[$name] = $options;
    }
    static::$boxes = $boxes;
  }
}

?>
