<?php

  class homeController extends applicationController {

    function index() {

      # Get latest photos for the home page
      $mysqli = parent::dbConnect();

      $res = $mysqli->query('
        select
          mobile_photos.id,
          photo,
          title,
          mobile_users.name,
          from_unixtime(timestamp, "%M %e, %Y \at %l:%i%p") as date
        from mobile_photos
        inner join mobile_users
        on user_id = mobile_users.id
        order by timestamp desc
        limit 12
      ');

      while ($row = $res->fetch_assoc()) {
        $photos[] = $row;
      }

      # Initialize and inflate the template
      $tpl = parent::tpl()->loadTemplate('index');

      print $tpl->render(array_merge(parent::getGlobalTemplateData(),
        array(
          'is_index_page' => true,
          'photos' => $photos
        )
      ));
    }

    # Display one photo
    function photo($params) {
      $photoId = $params['id'];

      # Get the photo's details from the database
      $mysqli = parent::dbConnect();

      $stmt = $mysqli->prepare('
        select
          mobile_photos.id,
          photo,
          title,
          mobile_users.name,
          from_unixtime(timestamp, "%M %e, %Y \at %l:%i%p") as date
        from mobile_photos
        inner join mobile_users
        on user_id = mobile_users.id
        where mobile_photos.id = ?
        limit 1
      ');

      $stmt->bind_param('s', $photoId);
      $stmt->execute();
      $stmt->bind_result($id, $photo, $title, $name, $date);

      while ($stmt->fetch()) {
        $photoVars = array(
          'id' => $id,
          'photo' => $photo,
          'title' => $title,
          'name' => $name,
          'date' => $date
        );
      }

      # Initialize and inflate the template
      $tpl = parent::tpl()->loadTemplate('photo');

      print $tpl->render(array_merge(parent::getGlobalTemplateData(), $photoVars));
    }

    # Handle logging the user in
    function login() {

      if ($_POST['username'] != '' && $_POST['password'] != '') {
        $md5Password = md5($_POST['password']);

        $mysqli = parent::dbConnect();
        $stmt = $mysqli->prepare('select count(*) from mobile_users where username = ? and password = ? limit 1');
        $stmt->bind_param('ss', $_POST['username'], $md5Password);
        $stmt->execute();
        $stmt->bind_result($col1);


        while ($stmt->fetch()) {
          if ($col1 == 1) {
            # Set the msushi cookie
            setcookie('msushi', $md5Password, time() + (86400 * 30)); // Cookie is good for 30 days
          }
        }
      }

      header('Location: /');
    }

    # Handle logging the user out
    function logout() {
      unset($_COOKIE['msushi']);
      setcookie('msushi', '', time() - 3600);

      header('Location: /');
    }
  }

?>
