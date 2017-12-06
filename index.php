<?php

  # Get Composer-installed libraries
  require 'vendor/autoload.php';

  # Include Controllers
  require_once('controllers/applicationController.php');
  require_once('controllers/homeController.php');

  # Use AltoRouter to map routes
  $router = new AltoRouter();
  $router->map('GET', '/', 'homeController#index');
  $router->map('POST', '/login', 'homeController#login');
  $router->map('GET', '/logout', 'homeController#logout');

  $match = $router->match();

  if ($match === false) {
    header("HTTP/1.0 404 Not Found");
    print "Not found.";
  }
  else {
    list( $controller, $action ) = explode( '#', $match['target'] );

    if ( is_callable(array($controller, $action)) ) {
        call_user_func_array(array($controller, $action), array($match['params']));
    } else {
        // here your routes are wrong.
        // Throw an exception in debug, send a  500 error in production
    }
  }
?>
