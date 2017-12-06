<?php

  class applicationController {

    # Provide an object with which to interface with the databae
    protected static function dbConnect() {
      return new mysqli($_SERVER['dbHost'], $_SERVER['dbUser'], $_SERVER['dbPassword'], $_SERVER['dbDatabase']);
    }

    # Provide a Mustache template object that descendant classes can access
    protected static function tpl() {
      Mustache_Autoloader::register();
      $mustache = new Mustache_Engine(array(
        "loader" => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/../tpl'),
        "partials_loader" => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/../tpl/partials')
      ));

      return $mustache;
    }

    # Provide globally-available template data
    protected static function getGlobalTemplateData() {
      $data = array();

      # Determine whether or not the user has a login cookie, and if they do, whether or not they are a valid user
      //print var_export($_COOKIE, true);

      if ($_COOKIE['msushi'] && self::isValidUser($_COOKIE['msushi'])) {
        $data['is_logged_in'] = true;
        $data['is_logged_out'] = false;
      }
      else {
        $data['is_logged_in'] = false;
        $data['is_logged_out'] = true;
      }

      return $data;
    }

    # Figure out if the user is logged in by checking their msushi cookie, if one exists
    protected static function isValidUser($msushiCookie) {
      if ($msushiCookie) {
        $mysqli = self::dbConnect();
        $stmt = $mysqli->prepare('select count(*) from mobile_users where password = ? limit 1');
        $stmt->bind_param('s', $msushiCookie);
        $stmt->execute();
        $stmt->bind_result($col1);

        while ($stmt->fetch()) {
          if ($col1 == 1) {
            $isValid = true;
          }
        }
      }
      else {
        $isValid = false;
      }

      return $isValid;
    }
  }
?>
