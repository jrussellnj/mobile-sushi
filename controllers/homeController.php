<?php

  class homeController extends applicationController {

    # How many photos to get for the home page
    private static $limit = 12;

    # Home page
    public static function index() {

      # Get the first page of photos
      $photos = self::getPhotos();

      # Initialize and inflate the template
      $tpl = parent::tpl()->loadTemplate('index');

      print $tpl->render(array_merge(parent::getGlobalTemplateData(),
        array(
          'photos' => $photos
        )
      ));
    }

    public static function morePhotos() {
      $pageNumber = $_GET['page'];

      # Get a new page of photos
      $photos = self::getPhotos($pageNumber);

      # Initialize and inflate the template
      $tpl = parent::tpl()->loadPartial('ajax/more_photos');

      print $tpl->render(array(
        'photos' => $photos
      ));
    }

    # Photo details page
    public static function photo($params) {
      $photoId = $params['id'];

      # Get database handler
      $mysqli = parent::dbConnect();

      # Get the logged-in user's ID
      $userId = self::getLoggedInUsersId();

      # Get the photo's details from the database
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

      # Get the photo's comments
      $stmt = $mysqli->prepare('
        select
          mobile_comments.id,
          mobile_users.name,
          mobile_users.id,
          comment,
          from_unixtime(date, "%M %e, %Y \at %l:%i%p") as date
        from mobile_comments
        inner join mobile_photos
        on photo_id = mobile_photos.id
        inner join mobile_users
        on mobile_comments.user_id = mobile_users.id
        where photo_id = ?
        order by mobile_comments.date asc
      ');

      $stmt->bind_param('s', $photoId);
      $stmt->execute();
      $stmt->bind_result($id, $name, $authorId, $comment, $date);

      while ($stmt->fetch()) {
        $comments[] = array(
          'id' => $id,
          'name' => $name,
          'comment' => $comment,
          'date' => $date,
          'logged_in_user_owns_comment' => $userId == $authorId
        );
      }

      # Initialize and inflate the template
      $tpl = parent::tpl()->loadTemplate('photo');

      print $tpl->render(array_merge(
        parent::getGlobalTemplateData(),
        $photoVars,
        array(
          'comments' => $comments
        )
      ));
    }

    # Post a comment
    public static function leaveComment() {

      # Get the database handler
      $mysqli = parent::dbConnect();

      # Get the logged-in user's ID
      $userId = self::getLoggedInUsersId();

      if ($userId) {
        # Insert the comment into the database
        $stmt = $mysqli->prepare('
          insert into mobile_comments
          (comment, photo_id, user_id, date)
          values (?, ?, ?, UNIX_TIMESTAMP())
        ');

        $stmt->bind_param('sss', $_POST['comment'], $_POST['photo_id'], $userId);
        $returnValue = $stmt->execute();
      }
      else {
        $returnValue = 0;
      }

      print "{ \"success\": $returnValue }";
    }

    # Delete a comment
    public static function deleteComment() {
      $commentId = $_POST['id'];

      # Get the ID of the logged-in user
      $loggedInUserId = self::getLoggedInUsersId();

      # Get the database handler
      $mysqli = parent::dbConnect();

      # First, make sure the user actually owns this comment before allowing them to delete it
      $stmt = $mysqli->prepare('select user_id from mobile_comments where id = ?');
      $stmt->bind_param('s', $commentId);
      $stmt->execute();
      $stmt->bind_result($commentOwnerUserId);
      $stmt->fetch();
      $stmt->close();

      if ($loggedInUserId == $commentOwnerUserId) {
        $stmt = $mysqli->prepare('delete from mobile_comments where id = ?');
        $stmt->bind_param('s', $commentId);
        $returnValue = $stmt->execute();
      }
      else {
        $returnValue = 0;
      }

      print "{ \"success\": $returnValue }";
    }

    # Handle logging the user in
    public static function login() {

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

      # Send the user back to the page they were on, if we have this information
      $location = ($_SERVER['HTTP_REFERER'] != '') ? $_SERVER['HTTP_REFERER'] : '/';

      header("Location: $location");
    }

    # Handle logging the user out
    public static function logout() {
      unset($_COOKIE['msushi']);
      setcookie('msushi', '', time() - 3600);

      # Send the user back to the page they were on, if we have this information
      $location = ($_SERVER['HTTP_REFERER'] != '') ? $_SERVER['HTTP_REFERER'] : '/';

      header("Location: $location");
    }

    # Retrieve photos from the database
    private static function getPhotos($page = 0) {

      # Get latest photos for the home page
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
        order by timestamp desc
        limit ?
        offset ?
      ');

      $offset = self::$limit * $page;
      $stmt->bind_param('ss', self::$limit, $offset);
      $stmt->execute();
      $stmt->bind_result($id, $photo, $title, $name, $date);

      $photos = array();

      while ($row = $stmt->fetch()) {
        $photos[] = array(
          'id' => $id,
          'photo' => $photo,
          'title' => $title,
          'name' => $name,
          'date' => $date
        );
      }

      return $photos;
    }

    # Get the logged-in user's ID
    private static function getLoggedInUsersId() {

      # Get the user's ID
      $mysqli = self::dbConnect();
      $stmt = $mysqli->prepare('select count(*) from mobile_users where password = ? limit 1');
      $stmt->bind_param('s', $_COOKIE['msushi']);
      $stmt->execute();
      $stmt->bind_result($userId);
      $stmt->fetch();
      $stmt->close();

      return $userId;
    }
  }

?>
