document.addEventListener('DOMContentLoaded', function() {
    var addCommentHeading = document.getElementById('add-comment-heading');
    var commentForm = document.getElementById('comment-form');

    if (addCommentHeading && commentForm) {
        addCommentHeading.addEventListener('click', function() {
            if (commentForm.style.display === 'none' || commentForm.style.display === '') {
                commentForm.style.display = 'block';
                commentForm.style.maxHeight = commentForm.scrollHeight + "px";
            } else {
                commentForm.style.display = 'none';
            }
        });
    }
});
