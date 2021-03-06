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
    foreach(loopable(static::$boxes) as $id => $box){
      $old_cb = isset($box['show_on_cb']) ? $box['show_on_cb'] : false;
      $box['show_on_cb'] = function($cmb) use($klass, $old_cb) {
        $user = static::get_currently_edited_user();
        if(!$user) return false;

        $user_belongs = static::user_belongs($user);
        if($user_belongs){
          if($old_cb){
            $cb_result = $old_cb($cmb);
            return $cb_result;
          }
          return true;
        }
        return false;
      };
      $boxes[$id] = $box;
    }
    static::$boxes = $boxes;
  }

  # TODO: if WP_DEBUG always create; otherwise create only if not exists
  static function create_content_type(){
    remove_role(static::name());
    $inherits_from = get_role( static::$inherits_from );
    $capabilities = array_merge($inherits_from->capabilities, static::$capabilities);
    # adds the role's capabilities to administrators too.
    if( !empty(static::$capabilities) ){
      $admin = get_role('administrator');
      foreach (static::$capabilities as $capability) {
        $admin->add_cap($capability);
      }
    }
    add_role(static::name(), static::$label, $capabilities );
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
    } elseif(isset($_GET['user_id'])) { #otherwise there's always an user_id as GET param
      return get_user_by('ID', $_GET['user_id']);
    } else { # ...unless it's a new user page and it doesn't exist yet
      return null;
    }
  } 

  # returns true if the given user belongs to this class
  static function user_belongs($user){
    return  in_array(static::name(), $user->roles) ||
            (static::$allow_admin && in_array('administrator', $user->roles));
  }

  public function is_a (string $role) {
    return static::name() === $role || (static::$allow_admin && in_array('administrator', $this->roles));
  }


  static function find ($id, bool $build = true) {
    $user = get_user_by('ID', $id);
    if ($user === null) return null;
    if (!static::user_belongs($user)) return null;
    return new static($user, $build);
  } 

  # fetches an user by given ID or the current one
  function __construct($arg = false, bool $build = true){
    if(!$arg){
      $arg = wp_get_current_user() ;
    } elseif(is_numeric($arg)){
      $arg = get_user_by('ID', $arg);
    }
    $this->base = $arg;
    parent::__construct($this->base, $build);
  }

}

?>
