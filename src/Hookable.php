<?php

namespace Bricks;

trait Hookable
{

  static function set_hooks()
  {
    if (isset(static::$actions) && !empty(static::$actions)) {
      foreach (static::$actions as $action => $args) {
        if (!is_array($args)) {
          $args = [$args];
        }
        static::add_action($action, $args[0], $args[1] ? : 10, $args[2] ? : 0);
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
    add_filter($name, [$class, $function], $priority, $num_args);
  }

}
