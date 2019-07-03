<?php

namespace Bricks;

trait Hookable
{
  /**
   * Installs WP hooks specified in the static arrays
   * $actions and $filters
   * @return void
   */
  static function set_hooks()
  {
    if (isset(static::$actions) && !empty(static::$actions)) {
      foreach (static::$actions as $action => $args) {
        is_array($args) || $args = [$args];
        isset($args[1]) || $args[1] = 10;
        isset($args[2]) || $args[2] = 0;
        static::add_action($action, $args[0], $args[1], $args[2]);
      }
    }

    if (isset(static::$filters) && !empty(static::$filters)) {
      foreach (static::$filters as $filter => $args) {
        if (!is_array($args)) {
          $args = [$args];
        }
        static::add_filter($filter, $args[0], $args[1] ? : 10, $args[2] ? : 1);
      }
    }
  }

  static function add_action(string $name, string $function, int $priority, int $num_args)
  {
    $class = get_called_class();
    add_action($name, [$class, $function], $priority, $num_args);
  }

  static function add_filter(string $name, string $function, int $priority, int $num_args)
  {
    $class = get_called_class();
    add_filter($name, [$class, $function], $priority, $num_args);
  }

}
