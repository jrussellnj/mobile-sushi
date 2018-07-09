<?php

  class accountController extends applicationController {
    public static function index() {

      # Get the ID of the logged-in user
      $loggedInUserId = parent::getLoggedInUsersId();

      if (!$loggedInUserId) {
        header("Location: /");
      }
      else {

        # Get the database handler
        $mysqli = parent::dbConnect();

        # Get the user's current email address
        $stmt = $mysqli->prepare('select name, username, author_email from mobile_users where id = ?');
        $stmt->bind_param('s', $loggedInUserId);
        $stmt->execute();
        $stmt->bind_result($displayname, $username, $userEmailAddress);
        $stmt->fetch();
        $stmt->close();

        # Initialize and inflate the template
        $tpl = parent::tpl()->loadTemplate('account');

        print $tpl->render(array_merge(parent::getGlobalTemplateData(),
          array(
            'displayname' => $displayname,
            'username' => $username,
            'emailaddress' => $userEmailAddress,
            'success' => isSet($_GET['success']) ? (($_GET['success'] == 1) ? '✔︎ Successfully updated!' : '❌ Oh no! Something went wrong.') : null
          )
        ));
      }
    }

    public static function update() {

      $updatedDisplayName = $_POST['displayname'];
      $updatedUsername = $_POST['username'];
      $updatedEmailAddress = $_POST['address'];
      $updatedPassword = $_POST['password'];

      # Get the ID of the logged-in user
      $loggedInUserId = parent::getLoggedInUsersId();

      # Get the database handler
      $mysqli = parent::dbConnect();

      # Update the user's email, password, or both
      if ($updatedPassword != '') {
        $md5Password = md5($updatedPassword);

        $stmt = $mysqli->prepare('
          update mobile_users
          set name = ?, username = ?, author_email = ?, password = ?
          where id = ?
        ');

        $stmt->bind_param('sssss', $updatedDisplayName, $updatedUsername, $updatedEmailAddress, $md5Password, $loggedInUserId);
      }
      elseif ($updatedEmailAddress != '') {
        $stmt = $mysqli->prepare('
          update mobile_users
          set name = ?, username = ?, author_email = ?
          where id = ?
        ');

        $stmt->bind_param('ssss', $updatedDisplayName, $updatedUsername, $updatedEmailAddress, $loggedInUserId);
      }

      $returnValue = $stmt->execute();

      header("Location: /account?success=" . $returnValue);
    }
  }

?>
