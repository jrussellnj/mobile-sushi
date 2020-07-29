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
        'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/../tpl'),
        'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/../tpl/partials')
      ));

      return $mustache;
    }

    # Provide globally-available template data
    protected static function getGlobalTemplateData() {
      $data = array();

      # Determine whether or not the user has a login cookie, and if they do, whether or not they are a valid user
      if (isset($_COOKIE['msushi']) && self::isValidUser($_COOKIE['msushi'])) {
        $data['is_logged_in'] = true;
        $data['is_logged_out'] = false;

        # Get unread comment information
        $data['unread_comments'] = self::getUnreadComments();
      }
      else {
        $data['is_logged_in'] = false;
        $data['is_logged_out'] = true;
      }

      return $data;
    }

    # Figure out if the user is logged in by checking their msushi cookie, if one exists
    protected static function isValidUser($msushiCookie) {
      $isValid = false;

      if ($msushiCookie) {
        $mysqli = self::dbConnect();
        $stmt = $mysqli->prepare('select count(*) from mobile_users where token = ? limit 1');
        $stmt->bind_param('s', $msushiCookie);
        $stmt->execute();
        $stmt->bind_result($col1);

        while ($stmt->fetch()) {
          if ($col1 == 1) {
            $isValid = true;
          }
        }
      }

      return $isValid;
    }

    # Get the logged-in user's ID
    protected static function getLoggedInUsersId() {
      $mysqli = self::dbConnect();
      $stmt = $mysqli->prepare('select id from mobile_users where token = ? limit 1');
      $stmt->bind_param('s', $_COOKIE['msushi']);
      $stmt->execute();
      $stmt->bind_result($userId);
      $stmt->fetch();
      $stmt->close();

      return $userId;
    }

    # Get information about the logged-in user's unread comments
    private static function getUnreadComments() {
      $userId = self::getLoggedInUsersId();
      $totalNewComments = 0;
      $comments = array();

      if ($userId) {
        $mysqli = self::dbConnect();

        # Get the latest comments the user missed and add them to the unseen comments table
        $stmt = $mysqli->prepare('select id from mobile_comments where mobile_comments.date > (select unix_timestamp(last_visited) from mobile_users where id = ?)');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $stmt->bind_result($commentId);

        $unseenCommentIds = array();

        while ($stmt->fetch()) {
          $unseenCommentIds[] = $commentId;
        }

        foreach($unseenCommentIds as $id) {
          $stmt = $mysqli->prepare('insert into mobile_comments_unseen (comment_id, unseen_by_user_id) values (?, ?)');
          $stmt->bind_param('ss', $id, $userId);
          $stmt->execute();
        }


        # Update the user's last_visited value
        $stmt = $mysqli->prepare('update mobile_users set last_visited = now() where id = ? limit 1');
        $stmt->bind_param('s', $userId);
        $stmt->execute();


        # Get full photo information for the unread comments
        $stmt = $mysqli->prepare('
          select mobile_photos.id, trim(replace(title, "\n", "")) as title, count(mobile_comments.id)
          from mobile_comments_unseen
          inner join mobile_comments on comment_id = mobile_comments.id
          inner join mobile_photos on mobile_comments.photo_id = mobile_photos.id
          where unseen_by_user_id = ?
          group by mobile_photos.id
        ');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $stmt->bind_result($photoId, $title, $unreadCommentsOnPhoto);

        while ($stmt->fetch()) {
          $comments[] = array(
            'photo_id' => $photoId,
            'title' => ($title != '') ? $title : "Untitled ($photoId)",
            'unread_comments_on_photo' => $unreadCommentsOnPhoto
          );

          $totalNewComments += $unreadCommentsOnPhoto;
        }
      }

      return array(
        'total_unread_comments' => $totalNewComments,
        'comments' => $comments
      );
    }
  }

?>
