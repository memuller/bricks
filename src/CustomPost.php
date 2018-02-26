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

  static function find ($id, bool $build = true) {
    $post = get_post($id);
    if ($post === null) return null;
    if ($post->post_type !== static::name()) return null;
    return new static($post, $build);
  } 

  function __construct($arg = false, $build = true){
    if(!$arg){
      $this->base = $GLOBALS['post'];
    } elseif(is_numeric($arg)){
      $this->base = get_post($arg);
    } else {
      $this->base = $arg;
    }
    parent::__construct($this->base, $build);
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

  static function on_save_hook($post_id, $post, $update) {
    $class = get_called_class();
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return ;
    if ($class::name() != $post->post_type) return ;
    $obj = new $class($post_id);
    remove_action('save_post', [$class, 'on_save_hook'], 30);
    $class::on_save($obj, $update);
    add_action('save_post', [$class, 'on_save_hook'], 30, 3);
  }

  static function setup_hooks(){
    parent::setup_hooks();
    $class = get_called_class();

    if (is_callable([$class, 'on_views_edit'])){
      add_filter("views_edit-".static::name(), [$class, 'on_views_edit']);
    }

    if (is_callable([$class, 'on_save'])){
      add_action('save_post', [$class, 'on_save_hook'] , 30, 3);
    }

    if (is_admin()) {
      if (is_callable([$class, 'on_parse_query_admin'])) {
        add_filter('parse_query', function($query) use($class) {
          if (!$query->is_main_query()) return ;
          if ($query->get('post_type') != $class::name()) return ;
          $class::on_parse_query_admin($query);
        });
      }
    }

    if (is_callable([$class, 'on_parse_query'])) {
      add_filter('parse_query', function($query) use($class) {
        if (!is_admin() && !$query->is_main_query()) return ;
        if ($query->get('post_type') != $class::name()) return ;
        $class::on_parse_query_admin($query);
      });
    }

    if (is_array($class::$filters) && isset($class::$filters['search'])) {
      if (is_admin()) {
        add_action('the_posts', function($posts) use($class) {
          $current_screen = get_current_screen();
          if ($current_screen->base !== 'edit' || $current_screen->post_type !== $class::name() || !isset($_GET['s'])) return $posts;
            $additional_posts = $class::query_items('search', ['s' => $_GET['s']]);
            $posts = array_merge($posts, $additional_posts);

          return $posts;
        });
      }
    }

    if (static::$hide_add) {
      add_action('admin_menu', function() use($class) {
        $post_type = $class::name();
        global $submenu;
        if (isset(static::$hide_add)) {
          unset($submenu["edit.php?post_type=$post_type"][10]);
          if (isset($_GET['post_type']) && $_GET['post_type'] == $post_type) {
            echo '<style type="text/css">.page-title-action { display:none; }</style>';
          }
        }
      });
    }
  }

}

?>
