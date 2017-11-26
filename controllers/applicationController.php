<?php

  class applicationController {
    
    # Provide a Mustache template object that descendant classes can access
    protected static function tpl() {
      Mustache_Autoloader::register();
      $mustache = new Mustache_Engine(array(
        "loader" => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/../tpl'),
        "partials_loader" => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/../tpl/partials')
      ));
      
      return $mustache;
    }

    protected static function dbConnect() {
      return new mysqli($_SERVER['dbHost'], $_SERVER['dbUser'], $_SERVER['dbPassword'], $_SERVER['dbDatabase']);
    }
  }
?>
