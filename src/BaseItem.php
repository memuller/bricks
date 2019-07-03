<?php
namespace Bricks;
use WP_Query, WP_User_Query;

class BaseItem {
  static  $fields = array(),
          $boxes = array(),
          $columns = array(), $hide_columns = array(),
          $name, $label, $creation_parameters,
          $ajax_actions, $rest_actions, $filters, $views,
          $has_one = false, $has_many = false, $belongs_to = false,
          $build = true,
          $map_getters = false;

  public $base, $meta;

  static function init(){
    static::prepare_parameters();
    static::prepare_relationships();
    static::$build && static::create_content_type();
    static::prepare_metaboxes();
    static::setup_hooks();
    static::create_metaboxes();
    static::set_columns();
    static::hook_ajax_actions();
    static::hook_rest_actions();
    static::register_meta();
  }

  static function setup_hooks(){
    $class = get_called_class();

    if (static::$views && sizeof(static::$views) > 0) {
      if (static::$content_type == 'post') {
        add_filter('views_edit-'.static::name(), [$class, 'hook_views']);
      } else {
        add_filter('views_users', function ($views) use ($class) {
          if (!(isset($_GET['role'])) || $_GET['role'] != $class::name()) return $views;
          return $class::hook_views($views);
        });
      }
    }
  }

  static function hook_views ($views) {
    foreach (static::$views as $view => $options) {
      if ($options == false && isset($views[$view])) {
        unset($views[$view]); continue;
      }

      $base_url = static::$content_type == 'post' ? '?post_type='.static::name() : '?user_role='.static::name();

      if (isset($options['url'])) {
        if ($options['url'][0] == '&') {
          $url = $base_url.$options['url'];
        } else {
          $url = $options['url'];
        }
      } else {
        $filter_name = isset($options['filter']) ? $options['filter'] : $view;
        $url = "$base_url&filter=$filter_name";

      }
      if (isset($options['count'])) {
        $found_query = new WP_Query(static::build_query_params($filter_name));
        $found_num = $found_query->found_posts;
        $found = sprintf('<span class="count">(%s)</span>', $found_num);
      }
      if ('?'.$_SERVER['QUERY_STRING'] == $url) {
        $current = 'current';
      } else { $current = ''; }

      $link = sprintf('<a class="%s" href="%s">%s %s</a>', $current, $url, $options['label'], $found);
      $views[$view] = $link;
    }
    return $views;
  }

  static function hook_ajax_actions () {
    $class = get_called_class();
    if (!static::$ajax_actions || sizeof(static::$ajax_actions) == 0) return ;
    foreach (static::$ajax_actions as $name => $method) {
      $prefix = 'wp_ajax_';
      if (is_int($name)) $name = $method;
      if (strpos($name, '_nopriv')) {
        $name = str_replace('_nopriv', '', $name);
        $prefix .= 'nopriv_';
      }
      $action_name = sprintf("%s_%s_%s", $prefix, static::name(), $name);
      add_action($action_name, [$class, $method]);
    }
  }

  static function hook_rest_actions () {
    $class = get_called_class();
    if (isset(static::$rest_actions) && sizeof(static::$rest_actions) > 0) {
      add_action('rest_api_init', function() use($class){
        $namespace = strtolower(BRICKS_NAMESPACE);
        $version = 'v1';
        foreach (static::$rest_actions as $action => $options) {
          $callback = isset($options['callback']) ? [$class, $options['callback']] : [ $class, 'rest_'.$action ];
          $route = isset($options['route']) ? $options['route'] : "/$action";
          $options['callback'] = function($request) use ($class, $callback) {
            $params = $request->get_url_params();
            $data = $request->get_body_params();
            call_user_func_array($callback, [$params, $data, $request]);
          };
          register_rest_route("$namespace/$version", $route, $options);
        }
      });
    }
  }

  static function belonging_to (string $field, $id) {
    if(!isset(static::$belongs_to) || !isset(static::$belongs_to[$field])) return null;
    if (!is_numeric($id)) $id = $id->ID;
    $query = [
      'meta_query' => [
        [ 'key' => $field, 'value' => $id ]
      ]
    ];
    return static::all($query);
  }

  static function prepare_metaboxes(){
    $boxes = static::$boxes;
    if ( $boxes !== false && sizeof($boxes) == 0) {
      if (!static::$fields || sizeof(static::$fields) == 0) return ;
      $box_name = "default";
      if (isset(static::$slug)) {
        $box_name.='_'.static::$slug;
      }

      $fields = array_filter(static::$fields, function($value, $key) {
        if (!isset($value['display'])) return true;
        if ($value['display'] === false) return false;
        return true;
      }, ARRAY_FILTER_USE_BOTH);

      $boxes[$box_name] = [
        'title' => ' ',
        'fields' => array_keys($fields)
      ];
    }
    static::$boxes = $boxes;
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
    foreach(loopable(static::$belongs_to) as $key => $value){
      if (is_numeric($key)){
        $parent = $value;
        $options = [];
      } else {
        $parent = $key;
        $options = $value;
      }
      if (!isset($fields[$parent])){
        $parent_class_name = isset($options['class']) ? $options['class'] : to_camel_case($parent);
        $parent_class = sibling_class($parent_class_name, $class);
        $is_many = isset($options['many']) && $options['many'];
        if (!$is_many) {
          $fields[$parent] = [
            'id'                => $parent_class::name(),
            'name'              => $parent_class::label(),
            'type'              => 'select',
            'show_option_none'  => false,
            'options_cb'        => function() use ($parent_class){
              $posts = $parent_class::all();
              $options = array();
              $post = null;
              foreach($posts as $post){
                $options[$post->ID] = $post->title;
              }
              return $options;
            }
          ];
        } else {
          $fields[$parent] = [
            'id'          => $parent_class::name(),
            'name'        => $parent_class::label(),
            'type'        => 'posts_search',
            'model'       => $parent_class_name,
            'repeatable'  => true,
            'multiple'    => true,
            'default'     => []
          ];
        }
      }
    }

    static::$fields =& $fields;
  }

  static function create_metaboxes(){
    $klass = get_called_class(); $name = static::name();
    $print = false;
    if (!static::$boxes) return ;
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
        if (isset($parameters['display']) && !$parameters['display']) continue;
        $parameters['id'] = $field_name;
        if(!isset($parameters['type'])){
          $parameters['type'] = 'text';
        }
        $field_parameters[]= $parameters;
      }
      # hooks box creation
      add_action('cmb2_admin_init', function() use($box_parameters, $field_parameters, $print){
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

    $has = [
      'add' => !empty($klass::$columns),
      'hide' => !empty($klass::$hide_columns) ]
    ;
    if($has['add'] || $has['hide']){
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

      add_filter($filters['set'], function($columns) use($klass, $has) {
        # skips column if we're on a user page that isn't listing this class user role
        if ($klass::$content_type == 'user'){
          if (!(isset($_GET['role'])) || $_GET['role'] != $klass::name()){
            return $columns;
          }
        }

        if ($has['add']) {
          $columns_to_add = $klass::$columns;
          # if there's a date column, moves it so it's always the last one
          if(isset($columns['date'])){
            unset($columns['date']);
            $columns_to_add['date'] = __('Date');
          }

          foreach($columns_to_add as $name => $label){
            $columns[$name] = __($label);
          }
        }

        if ($has['hide']) {
          foreach($klass::$hide_columns as $column) {
            unset($columns[$column]);
          }
        }
        return $columns;
      });

      if('post' == static::$content_type){
        add_action($filters['display'], function($column, $ID) use($klass){
          $obj = new $klass($ID);
          $out = property_or_method($obj, $column);
          echo $out ? $out  : '—' ;
        }, 10, 2);
      } else {
        add_filter($filters['display'], function($out, $column, $ID) use($klass){
          $obj = new $klass($ID);
          $out = property_or_method($obj, $column);
          return $out ? $out : '—';
        }, 20, 3);
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

  function save() {

  }

  function __construct($base, $build = true){
    if ($build) {
      $all_meta = get_metadata(static::$content_type, $this->base->ID);
      foreach($all_meta as $field_name => $field_values){
        if(static::has_field($field_name)){
          $is_multiple = isset(static::$fields[$field_name]['multiple']) && static::$fields[$field_name]['multiple'] === true;
          $this->meta[$field_name] = $is_multiple ? loopable($field_values) : $field_values[0];
        }
      }
    }
  }

  function __get($thing){
    if(static::has_field($thing)){
      if (isset($this->meta[$thing])) {
        $value = $this->meta[$thing];
      } else if (isset(static::$fields[$thing]['default'])) {
        $value = static::$fields[$thing]['default'];
      } else { return null; }
    } else {
      if (static::$map_getters) {
        $possible_getter_name = "get_$thing";
        if (isset($this->{$possible_getter_name}) && is_callable([$this, $possible_getter_name])) {
          $value = $this->{$possible_getter_name}();
        } else {
          return $this->base->{$thing};
        }
      } else {
        return $this->base->{$thing};
      }
    }
    $value = maybe_unserialize($value);
    return $value;
  }

  function delete (string $meta, $value = '') {
    $method = static::$content_type === 'post' ? 'delete_post_meta' : 'delete_user_meta';
    $method($this->ID, $meta, $value);
  }

  function label_for ($thing) {
    $value = $this->{$thing};
    if (isset(static::$fields[$thing]) && isset(static::$fields[$thing]['options'])){
      $value = (string) ($value);
      if (isset(static::$fields[$thing]['options'][$value])){
        $value = static::$fields[$thing]['options'][$value];
      }
    }
    return $value;
  }

  function __set($thing, $value){
    if(property_exists($this->base, $thing)){
      $this->base->{$thing} = $value;
      $this->save_base();
    } else {
      $this->meta[$thing] = $value;
      if (isset(static::$fields[$thing]['multiple']) && static::$fields[$thing]['multiple']) {
        $this->delete($thing);
        $this->push_meta($thing, $value);
      }
      \update_metadata(static::$content_type, $this->base->ID, $thing, $value);
    }
    return $value;
  }

  function push_meta (string $key, $values) {
    $values = loopable($values);
    foreach ($values as $value) {
      add_metadata(static::$content_type, $this->ID, $key, $value);
    }
  }

  static function build_query_params($params = array(), $additional_params = false) {
    if (is_string($params)) {
      $params = static::$filters[$params];
      if ($additional_params && is_array($additional_params)) {
        $params = recursive_replace($params, $additional_params);
      }
    }
    $class = get_called_class();
    if (static::$content_type == 'post') {
      $default_params = [
        'post_type'     => static::name()
      ];
    } else {
      $default_params = [
        'role' => static::name()
      ];
    }
    return array_merge($default_params, $params);
  }

  static function query_for ($params = array(), $additional_params = false) {
    $params = static::build_query_params($params, $additional_params);
    if (static::$content_type == 'post') {
      return get_posts($params);
    } else {
      return get_users($params);
    }
  }
  static function query_items ($params = array(), $additional_params = false) {
    $params = static::build_query_params($params, $additional_params);
    if (static::$content_type == 'post') {
      return get_posts($params);
    } else {
      return get_users($params);
    }
  }

  static function all($params = array(), $additional_params = false){
    $class = get_called_class();
    $items = static::query_items($params, $additional_params);
    return array_map(function($item) use($class){
      return new $class($item);
    }, $items);
  }

  static function first($params = array()) {
    $params['posts_per_page'] = 1;
    $result = static::all($params);
    if(!$result || sizeof($result) == 0) {
      return null;
    } else {
      return $result[0];
    }
  }

  static function register_meta() {
    if ( static::$fields ) {
      foreach ( static::$fields as $field => $field_options ) {
        $options = [
          'single' => ! ( isset($field_options['multiple']) && $field_options['multiple'] ),
          'show_in_rest' => ! ( isset($field_options['show_in_rest']) && $field_options['show_in_rest'] === false )
        ];
        register_meta( static::$content_type, $field, $options );
      }
    }
  }
}
