<?php

  # Get Composer-installed libraries
  require 'vendor/autoload.php';

  # Include Controllers
  require_once('controllers/applicationController.php');
  require_once('controllers/homeController.php');


  # Use AltoRouter to map routes
  $router = new AltoRouter();


  # Home page
  $router->map('GET', '/', 'homeController#index');
  $router->map('GET', '/more-photos', 'homeController#morePhotos');

  # Photo detail page
  $router->map('GET', '/photo/[i:id]', 'homeController#photo');

  # Commenting
  $router->map('POST', '/leave-comment', 'homeController#leaveComment');
  $router->map('POST', '/delete-comment', 'homeController#deleteComment');

  # Logging in and out
  $router->map('POST', '/login', 'homeController#login');
  $router->map('GET', '/logout', 'homeController#logout');

  # Explore page
  $router->map('GET', '/explore', 'homeController#explore');
  $router->map('GET', '/explore/results', 'homeController#exploreResults');

  # View a random photo
  $router->map('POST', '/get-random-photo', 'homeController#getRandomPhoto');


  # Figure out if the route is matched, and if it is, call its controller action
  $match = $router->match();

  if ($match === false) {
    header("HTTP/1.0 404 Not Found");
    print "Not found.";
  }
  else {
    list( $controller, $action ) = explode( '#', $match['target'] );

    if ( is_callable(array($controller, $action)) ) {
        call_user_func_array(array($controller, $action), array($match['params']));
    }
    else {
      header("HTTP/1.0 500 Internal Server Error");
    }
  }
?>
