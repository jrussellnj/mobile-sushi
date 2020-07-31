<?php

  class photoController extends applicationController {

    public static function delete($photo_id) {

      # Get the ID of the logged-in user
      $loggedInUserId = parent::getLoggedInUsersId();

      # Get the database handler
      $mysqli = parent::dbConnect();

      # First, make sure the user actually owns this photo before allowing them to edit its title
      $stmt = $mysqli->prepare('select user_id from mobile_photos where id = ?');
      $stmt->bind_param('s', $photo_id);
      $stmt->execute();
      $stmt->bind_result($photoOwnerUserId);
      $stmt->fetch();
      $stmt->close();

      error_log("Logged in user is $loggedInUserId");
      error_log("Photo owner user is $photoOwnerUserId");

      if ($loggedInUserId == $photoOwnerUserId) {
        $stmt = $mysqli->prepare('delete from mobile_photos where id = ?');
        $stmt->bind_param('s', $photo_id);
        $returnValue = $stmt->execute();
      }
      else {
        $returnValue = 0;
      }

      print "{ \"success\": $returnValue }";
    }
  }

?>
