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
      $userId = parent::getLoggedInUsersId();

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

      $comments = array();

      while ($stmt->fetch()) {
        $comments[] = array(
          'id' => $id,
          'name' => $name,
          'comment' => $comment,
          'date' => $date,
          'logged_in_user_owns_comment' => $userId == $authorId
        );
      }

      # Mark that the user has read all the comments for this photo
      $stmt = $mysqli->prepare('delete from mobile_comments_unseen where comment_id in (select id from mobile_comments where photo_id = ?) and unseen_by_user_id = ?');
      $stmt->bind_param('ss', $photoId, $userId);
      $stmt->execute();

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

    # Explore page - main
    public static function explore() {

      # Get database handler
      $mysqli = parent::dbConnect();

      # Get possible users to seach by
      $result = $mysqli->query('select id, name from mobile_users order by name');

      while ($row = $result->fetch_assoc()) {
        $users[] = array(
          'id' => $row['id'],
          'name' => $row['name']
        );
      }

      # Initialize and inflate the template
      $tpl = parent::tpl()->loadTemplate('explore');

      print $tpl->render(array_merge(
        parent::getGlobalTemplateData(),
        array(
          'users' => $users
        )
      ));
    }

    # Explore page - results
    public static function exploreResults() {

      # Get the page number and search parameters
      $pageNumber = $_GET['page'];
      $searchParams = array(
        'user_id' => $_GET['user_id'],
        'date_formatted' => $_GET['date_formatted']
      );

      # Populate the array used for Explore results page pagination
      $pagination = array(
        'user_id' => $searchParams['user_id'],
        'date_formatted' => $searchParams['date_formatted'],
        'next_page' => $pageNumber + 1,
        'prev_page' => ($pageNumber - 1) > 0 ? $pageNumber - 1 : 0,
        'show_prev_page' => $pageNumber - 1 >= 0
      );

      # Get the photos
      $photos = self::getPhotos($pageNumber, $searchParams);

      # Initialize and inflate the template
      $tpl = parent::tpl()->loadTemplate('explore_results');

      print $tpl->render(array_merge(parent::getGlobalTemplateData(),
        array(
          'photos' => $photos,
          'pagination' => $pagination
        )
      ));
    }

    # Post a comment
    public static function leaveComment() {

      # Get the database handler
      $mysqli = parent::dbConnect();

      # Get the logged-in user's ID
      $userId = parent::getLoggedInUsersId();

      if ($userId) {
        # Insert the comment into the database
        $stmt = $mysqli->prepare('
          insert into mobile_comments
          (comment, photo_id, user_id, date)
          values (?, ?, ?, UNIX_TIMESTAMP())
        ');

        $stmt->bind_param('sss', $_POST['comment'], $_POST['photo_id'], $userId);
        $returnValue = $stmt->execute();

        # Update the user's last_visited value so they don't get a notification from their own new comment
        $stmt = $mysqli->prepare('update mobile_users set last_visited = now() where id = ? limit 1');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
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
      $loggedInUserId = parent::getLoggedInUsersId();

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

        # Find a user by the supplised user name and password
        $stmt = $mysqli->prepare('select id from mobile_users where username = ? and password = ? limit 1');
        $stmt->bind_param('ss', $_POST['username'], $md5Password);
        $stmt->execute();
        $stmt->bind_result($userId);
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
          while ($stmt->fetch()) {

            # Create a token
            $salt = substr(strtr(base64_encode(openssl_random_pseudo_bytes(22)), '+', '.'), 0, 22);
            $token = crypt(microtime() * $userId, '$2y$12$' . $salt);

            # Set the msushi cookie
            setcookie('msushi', $token, time() + (86400 * 30)); // Cookie is good for 30 days
          }

          # Set the token in the database
          $stmt->close();

          $stmt = $mysqli->prepare('update mobile_users set token = ? where id = ? limit 1');
          $stmt->bind_param('ss', $token, $userId);
          $stmt->execute();
        }
      }

      # Send the user back to the page they were on, if we have this information
      $location = ($_SERVER['HTTP_REFERER'] != '') ? $_SERVER['HTTP_REFERER'] : '/';

      header("Location: $location");
    }

    # Handle logging the user out
    public static function logout() {

      # Unset the login token in the database
      $mysqli = parent::dbConnect();

      $stmt = $mysqli->prepare('select id from mobile_users where token = ? limit 1');
      $decodedCookie = urldecode($_COOKIE['msushi']);
      $stmt->bind_param('s', $decodedCookie);
      $stmt->execute();
      $stmt->bind_result($userId);
      $stmt->fetch();
      $stmt->close();

      $stmt = $mysqli->prepare('update mobile_users set token = ? where id = ? limit 1');
      $emptyString = '';
      $stmt->bind_param('ss', $emptyString, $userId);
      $stmt->execute();

      # Unset the msushi cookie
      unset($_COOKIE['msushi']);
      setcookie('msushi', '', time() - 3600);

      # Send the user back to the page they were on, if we have this information
      $location = ($_SERVER['HTTP_REFERER'] != '') ? $_SERVER['HTTP_REFERER'] : '/';

      header("Location: $location");
    }

    # Retrieve photos from the database
    private static function getPhotos($page = 0, $searchParams = array()) {

      # Get latest photos for the home page
      $mysqli = parent::dbConnect();

      # Figure out the offset for the queries below
      $offset = self::$limit * $page;

      # If we've sent in search params, figure out which query to use, and if not, 
      # use the general query that gets the latest photos
      if (count($searchParams) > 0) {

        # Asking for photos by user id
        if ($searchParams['user_id'] != '' && $searchParams['date_formatted'] == '') {
          $query = 'select
            mobile_photos.id,
            photo,
            replace(title, "\n", "") as title,
            mobile_users.name,
            from_unixtime(timestamp, "%M %e, %Y \at %l:%i%p") as date,
            (select count(id) from mobile_comments where photo_id = mobile_photos.id) as commentnum
          from mobile_photos
          inner join mobile_users
          on user_id = mobile_users.id
          where user_id = ?
          order by timestamp desc
          limit ?
          offset ?';

          $stmt = $mysqli->prepare($query);
          $stmt->bind_param('sss', $searchParams['user_id'], self::$limit, $offset);
        }

        # Asking for photos by date
        elseif ($searchParams['user_id'] == '' && $searchParams['date_formatted'] != '') {
          $query = 'select
            mobile_photos.id,
            photo,
            replace(title, "\n", "") as title,
            mobile_users.name,
            from_unixtime(timestamp, "%M %e, %Y \at %l:%i%p") as date,
            (select count(id) from mobile_comments where photo_id = mobile_photos.id) as commentnum
          from mobile_photos
          inner join mobile_users
          on user_id = mobile_users.id
          where timestamp between ? and ?
          order by timestamp desc
          limit ?
          offset ?';

          $startTime = strtotime($searchParams['date_formatted']);
          $endTime = $startTime + 86400;

          $stmt = $mysqli->prepare($query);
          $stmt->bind_param('ssss', $startTime, $endTime, self::$limit, $offset);
        }

        # Asking for photos by both user id and date
        elseif ($searchParams['user_id'] != '' && $searchParams['date_formatted'] != '') {
          $query = 'select
            mobile_photos.id,
            photo,
            replace(title, "\n", "") as title,
            mobile_users.name,
            from_unixtime(timestamp, "%M %e, %Y \at %l:%i%p") as date,
            (select count(id) from mobile_comments where photo_id = mobile_photos.id) as commentnum
          from mobile_photos
          inner join mobile_users
          on user_id = mobile_users.id
          where user_id = ? and timestamp between ? and ?
          order by timestamp desc
          limit ?
          offset ?';

          $startTime = strtotime($searchParams['date_formatted']);
          $endTime = $startTime + 86400;

          $stmt = $mysqli->prepare($query);
          $stmt->bind_param('sssss', $searchParams['user_id'], $startTime, $endTime, self::$limit, $offset);
        }
      }
      else {

        # Just get the latest photos
        $stmt = $mysqli->prepare('
          select
            mobile_photos.id,
            photo,
            replace(title, "\n", "") as title,
            mobile_users.name,
            from_unixtime(timestamp, "%M %e, %Y \at %l:%i%p") as date,
            (select count(id) from mobile_comments where photo_id = mobile_photos.id) as commentnum
          from mobile_photos
          inner join mobile_users
          on user_id = mobile_users.id
          order by timestamp desc
          limit ?
          offset ?
        ');

        $stmt->bind_param('ss', self::$limit, $offset);
      }

      $stmt->execute();
      $stmt->bind_result($id, $photo, $title, $name, $date, $commentnum);

      $photos = array();

      while ($row = $stmt->fetch()) {
        $photos[] = array(
          'id' => $id,
          'photo' => $photo,
          'title' => trim($title),
          'name' => $name,
          'date' => $date,
          'commentnum' => $commentnum
        );
      }

      return $photos;
    }

  }

?>
