<?php

  class apiController extends applicationController {

    # Get a random comment by user
    public static function randomCommentByUser($params) {

      if ($params['user']) {

        # Get database handler
        $mysqli = parent::dbConnect();

        # Get a random comment from the user whose username was provided
        $query = 'select comment
          from mobile_comments
          inner join mobile_users
          on mobile_comments.user_id = mobile_users.id
          where mobile_users.username like ?
          order by rand()
          limit 1';

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('s', $params['user']);
        $stmt->execute();
        $stmt->bind_result($comment);
        $stmt->fetch();
        $stmt->close();

        print "{ \"comment\": \"$comment\" }";
      }
      else {
        print "{ \"comment\": '' }";
      }
    }

  }

?>
