<?php

class Bricks {
  const VERSION = '0.0.2';
  const PRIORITY = 9999;

  public static $singleton = null;

  public static function instance() {
    if ( self::$singleton === null ) {
      self::$singleton = new self();
    }
    return self::$singleton;
  }

  private function __construct() {
    if ( ! defined('BRICKS_LOADED') ) {
      define( 'BRICKS_LOADED', self::PRIORITY );
    }

    $this->initialize();

    add_action('init', [$this, 'init'], self::PRIORITY);
  }

  private function initialize () {
    $this->load_libs();
    $this->register_autoloader();
  }

  public function init(){
    $this->load_models();
    $this->init_cmb2();
  }

  private function load_libs() {
    require_once 'src/Helpers.php';
    require_once 'src/BaseItem.php';
    require_once 'src/CustomUser.php';
    require_once 'src/CustomPost.php' ;
    require_once 'src/CustomSingle.php';
    require_once 'src/Hookable.php';
  }

  private function init_cmb2() {
    require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '../../cmb2/cmb2/init.php']);
  }

  private function register_autoloader(){
    spl_autoload_register(function(string $class) {
      if (defined('BRICKS_NAMESPACE')) {
        $class_path = explode('\\', $class);
        $class_name = $class_path[sizeof($class_path)-1];
        $namespace = $class_path[0];
        if(BRICKS_NAMESPACE == $namespace){
          $path = implode(DIRECTORY_SEPARATOR, [BRICKS_BASE_DIR, 'models', "$class_name.php"]);
          if (!file_exists($path)) return;
          require_once $path;
        }
      }
    });
  }

  private function load_models() {
    if ( defined('BRICKS_NAMESPACE') ) {
      foreach(glob(implode(DIRECTORY_SEPARATOR, [BRICKS_BASE_DIR, 'models', '*.php'])) as $file){
        $path = explode(DIRECTORY_SEPARATOR, $file);
        $class_name =  explode('.', $path[sizeof($path)-1])[0] ;
        $class = BRICKS_NAMESPACE.'\\'.$class_name;
        if(method_exists($class, 'init')){
          $class::init();
        }
      }
    }
  }

}

Bricks::instance();
?>
