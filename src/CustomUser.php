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
    # shows boxes only if an user belonging to this class is being edited
    foreach(static::$boxes as $id => $box){ 
      $box['show_on_cb'] = function() use($klass) {
        return static::user_belongs(static::get_currently_edited_user());
      };
      $boxes[$id] = $box;
    }
    static::$boxes = $boxes;
  }

  # TODO: if WP_DEBUG always create; otherwise create only if not exists
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

  # gets the user currently being edited in the admin
  static function get_currently_edited_user(){
    # if IS_PROFILE_PAGE, user is editing itself
    if(defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE){
      return wp_get_current_user();
    } else { #otherwise there's always an user_id as GET param
      return get_user_by('ID', $_GET['user_id']);
    }
  }

  # returns true if the given user belongs to this class
  static function user_belongs($user){
    return  in_array(static::$name, $user->roles) || 
            (static::$allow_admin && in_array('administrator', $user->roles));
  }

  # fetches an user by given ID or the current one
  function __construct($arg=false){
    if(!$arg){
      $arg = wp_get_current_user() ;  
    } elseif(is_numeric($arg)){
      $arg = get_user_by('ID', $arg);
    }
    $this->base = $arg;
    parent::__construct($this->base);
  }

}

?>