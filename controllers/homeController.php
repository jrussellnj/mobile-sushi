<?php

  class homeController extends applicationController {
    
    function index() {

      // Get latest photos for the home page
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

      // Initialize and inflate the template
      $tpl = parent::tpl()->loadTemplate('index');
      print $tpl->render(array(
        'photos' => $photos
      ));
    }
  }

?>
