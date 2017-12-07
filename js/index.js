$(document).ready(function() {
  // Handle logging in
  loggingIn();

  // Commenting
  hookUpCommenting();
});

function loggingIn() {

  // Bind to the Log In link
  var
    logInLink = $('#log-in'),
    logInOverlay = $('.dark-overlay.login'),
    dialogCloseLink = logInOverlay.find('.dialog-close-link');

  logInLink.click(function(e) {
    e.preventDefault();
    logInOverlay.fadeIn();
  });

  dialogCloseLink.click(function(e) {
    e.preventDefault();
    logInOverlay.fadeOut();
  });
}

function hookUpCommenting() {
  var
    commentForm = $('#comment-form'),
    deleteLinks = $('.delete-comment'),
    deleteConfirmationOverlay = $('.dark-overlay.delete-confirmation'),
    deleteCloseLink = deleteConfirmationOverlay.find('.dialog-close-link'),
    deleteCommentForm = $('#delete-comment-form');

  // Submit a comment
  commentForm.submit(function(e) {
    e.preventDefault();

    // Post the comment to the form action URL
    $.post(commentForm.attr('action'), commentForm.serialize(), function(data) {
      data = JSON.parse(data);

      if (data.success == 1) {
        location.reload();
      }
    });
  });

  // Clicking on 'delete' under a comment triggers the deletion confirmation dialog
  deleteLinks.click(function(e) {
    e.preventDefault();

    var
      thisId = $(this).data('comment-id');

    deleteConfirmationOverlay.find('input[name="comment_id"]').val(thisId);
    deleteConfirmationOverlay.fadeIn();

  });

  // Deletion form submit action
  deleteCommentForm.submit(function(e) {
    e.preventDefault();

    var
      commentId = $(this).find('input[name="comment_id"]').val();

    $.post('/delete-comment', { id: commentId }, function(data) {
      data = JSON.parse(data);

      if (data.success == 1) {
        location.reload();
      }
    });
  });

  // Deletion confirmation close link
  deleteCloseLink.click(function(e) {
    e.preventDefault();
    deleteConfirmationOverlay.fadeOut();
  });
}
