<?php
namespace Bricks;
use \WP_Roles;
class CustomUser extends BaseItem {
  static  $inherits_from = 'editor',
          $capabilities = array(),
          $content_type = 'user',
          $allow_admin = true;

  static function prepare_parameters(){
    $klass = get_called_class();
    $boxes = array();
    foreach(static::$boxes as $id => $box){ 
      $box['show_on_cb'] = function() use($klass) {
        return static::user_is(static::edit_user());
      };
      $boxes[$id] = $box;
    }
    static::$boxes = $boxes;
  }

  static function create_content_type(){
    remove_role(static::$name);
    $inherits_from = get_role( static::$inherits_from ); 
    $capabilities = array_merge($inherits_from->capabilities, static::$capabilities); 
    # adds the role's capabilities to administrators too.
    if( !empty(static::$capabilities) ){
      $admin = get_role('administrator');
      foreach (static::$capabilities as $capability) {
        $admin->add_cap($capability);
      }
    }
    add_role(static::$name, static::$label, $capabilities );
  }

  # true if this user's role is one of WP's default roles
  # TODO: actually do it
  static function is_default(){
  }

  static function edit_user(){
    if(defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE){
      return wp_get_current_user();
    } else {
      return get_user_by('ID', $_GET['user_id']);
    }
  }

  static function user_is($user){
    return  in_array(static::$name, $user->roles) || 
            (static::$allow_admin && in_array('administrator', $user->roles));
  }

  function __construct($arg=false){
    if(!$arg){
      $arg = wp_get_current_user() ;  
    }
    $this->base = $arg;
    parent::__construct($this->base);
  }

}

?>