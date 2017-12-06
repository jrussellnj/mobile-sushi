$(document).ready(function() {
  // Handle logging in
  loggingIn();
});

function loggingIn() {

  // Bind to the Log In link
  var
    logInLink = $('#log-in'),
    logInOverlay = $('#login-overlay'),
    dialogCloseLink = $('#dialog-close-link');
    logInDialog = $('#login-dialog');

  logInLink.click(function(e) {
    e.preventDefault();
    logInOverlay.fadeIn();
  });

  dialogCloseLink.click(function(e) {
    e.preventDefault();
    logInOverlay.fadeOut();
  });
}
