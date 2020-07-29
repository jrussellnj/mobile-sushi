$(document).ready(function() {
  // Handle logging in
  loggingIn();

  // Home page 'load more photos' functionality
  loadMorePhotos();

  // Home page unread comment bubble
  unreadComments();

  // Commenting
  hookUpCommenting();

  // Photo title editing
  hookUpTitleEditing();

  // Photo deletion
  hookUpPhotoDeletion();

  // Explore page date picker
  datePicker();
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

function hookUpTitleEditing() {
  var
    theTitle = $('#the-title'),
    editTitleLink = $('.edit-title'),
    editTitleForm = $('#edit-title-form');

  // Show the title editing form
  editTitleLink.click(function(e) {
    e.preventDefault();

    $(this).toggleClass('editing');
    theTitle.toggle();
    editTitleForm.toggleClass('show');
    editTitleForm.find('input[type="text"]').focus();
  });

  editTitleForm.submit(function(e) {
    e.preventDefault();

    // Send off the request for a title edit
    $.post('/edit-title', $(this).serialize(), function(data) {
      data = JSON.parse(data);

      if (data.success == 1) {
        location.reload();
      }
    });
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

    var thisId = $(this).data('comment-id');

    deleteConfirmationOverlay.find('input[name="comment_id"]').val(thisId);
    deleteConfirmationOverlay.fadeIn();

  });

  // Deletion form submit action
  deleteCommentForm.submit(function(e) {
    e.preventDefault();

    var commentId = $(this).find('input[name="comment_id"]').val();

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

function loadMorePhotos() {

  var morePhotosLink = $('#load-more-photos');

  // Store the data about which page of photos to get
  morePhotosLink.data('get-page', 1);

  morePhotosLink.click(function(e) {
    e.preventDefault();

    var
      pageNumber = $(this).data('get-page'),
      photoContainer = $('#photo-container');

    $.get('/more-photos', { page: pageNumber }, function(data) {
      photoContainer.append(data);

      var newPhotosOffset = $('#photo-container .photo-row:last-child').offset().top;
      $("html, body").stop().animate({scrollTop: newPhotosOffset}, 500);

      morePhotosLink.data('get-page', ++pageNumber);
    });
  });
}

function unreadComments() {
  var
    unreadLink = $('#unread-comments-bubble'),
    unreadDetails = $('#unread-comments-details');

  unreadLink.click(function(e) {
    e.preventDefault();
    unreadDetails.slideToggle(250);
  });
}

function datePicker() {
  var picker = new Pikaday({
    field: document.getElementById('datepicker'),
    format: 'YYYY-MM-DD',
    onSelect: function() {
      $('input[name="date_formatted"]').val(this.getDate());
    }
  });
}

function hookUpPhotoDeletion() {
  var
    deletePhotoLink = $('.delete-photo'),
    deletePhotoForm = $('#delete-photo-form');

  // Show the title editing form
  deletePhotoLink.click(function(e) {
    e.preventDefault();

    if (confirm("Really delete?")) {

      // Send off the request to delete the photo
      $.post('/delete-photo', deletePhotoForm.serialize(), function(data) {
        data = JSON.parse(data);

        if (data.success == 1) {
          window.location = '/';
        }
      });
    }
  });

}
