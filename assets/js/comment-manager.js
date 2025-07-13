/**
 * Comment Manager - Handles comment editing, deletion, and citation functionality
 * Works globally across post.php and user.php
 */
class CommentManager {
    constructor() {
        this.selectedText = '';
        this.lastValidSelection = '';
        this.activeTextarea = null;
        this.quoteButton = null;
        this.init();
    }

    init() {
        this.bindEventListeners();
        this.setupCitationFeature();
    }

    bindEventListeners() {
        // Use event delegation to handle dynamically added comments
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-comment-btn')) {
                e.preventDefault();
                this.startEdit(e.target);
            } else if (e.target.classList.contains('save-comment-btn')) {
                e.preventDefault();
                this.saveComment(e.target);
            } else if (e.target.classList.contains('cancel-edit-btn')) {
                e.preventDefault();
                this.cancelEdit(e.target);
            } else if (e.target.classList.contains('delete-comment-btn')) {
                e.preventDefault();
                this.deleteComment(e.target);
            } else if (e.target.classList.contains('reply-comment-btn')) {
                e.preventDefault();
                this.startReply(e.target);
            } else if (e.target.classList.contains('save-reply-btn')) {
                e.preventDefault();
                this.saveReply(e.target);
            } else if (e.target.classList.contains('cancel-reply-btn')) {
                e.preventDefault();
                this.cancelReply(e.target);
            }
        });

        // Handle Enter key in edit/reply textarea (Ctrl+Enter to save)
        document.addEventListener('keydown', (e) => {
            if (e.target.classList.contains('comment-edit-textarea') || e.target.id === 'comment_content') {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    const saveBtn = e.target.closest('.comment-edit-form').querySelector('.save-comment-btn');
                    if (saveBtn) {
                        this.saveComment(saveBtn);
                    }
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    const cancelBtn = e.target.closest('.comment-edit-form').querySelector('.cancel-edit-btn');
                    if (cancelBtn) {
                        this.cancelEdit(cancelBtn);
                    }
                }
            } else if (e.target.classList.contains('comment-reply-textarea')) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    const saveBtn = e.target.closest('.comment-reply-form').querySelector('.save-reply-btn');
                    if (saveBtn) {
                        this.saveReply(saveBtn);
                    }
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    const cancelBtn = e.target.closest('.comment-reply-form').querySelector('.cancel-reply-btn');
                    if (cancelBtn) {
                        this.cancelReply(cancelBtn);
                    }
                }
            }
        });
    }

    setupCitationFeature() {
        // Track text selection globally
        document.addEventListener('mouseup', () => {
            // Small delay to ensure selection is complete
            setTimeout(() => {
                this.handleTextSelection();
            }, 10);
        });

        // Track textarea focus/blur
        document.addEventListener('focusin', (e) => {
            if (this.isCommentTextarea(e.target)) {
                console.log('CommentManager: Textarea focused:', e.target.id);
                this.activeTextarea = e.target;
                // Don't clear selection immediately, give time for quote button to appear
                setTimeout(() => {
                    this.showQuoteButtonIfNeeded();
                }, 50);
            }
        });

        document.addEventListener('focusout', (e) => {
            if (this.isCommentTextarea(e.target)) {
                // Small delay to allow for quote button clicks
                setTimeout(() => {
                    if (!document.activeElement || !this.isCommentTextarea(document.activeElement)) {
                        this.hideQuoteButton();
                        this.activeTextarea = null;
                    }
                }, 200);
            }
        });
    }

    isCommentTextarea(element) {
        return element && (
            element.classList.contains('comment-edit-textarea') ||
            element.classList.contains('comment-reply-textarea') ||
            element.id === 'comment_content'
        );
    }

    handleTextSelection() {
        const selection = window.getSelection();
        this.selectedText = selection.toString().trim();
        
        // Store valid selection for later use
        if (this.selectedText.length >= 10) {
            this.lastValidSelection = this.selectedText;
        }
        
        console.log('CommentManager: Text selected:', this.selectedText.substring(0, 50) + (this.selectedText.length > 50 ? '...' : ''));
        console.log('CommentManager: Last valid selection:', this.lastValidSelection.substring(0, 50) + (this.lastValidSelection.length > 50 ? '...' : ''));
        console.log('CommentManager: Active textarea:', this.activeTextarea);
        
        if (this.activeTextarea) {
            this.showQuoteButtonIfNeeded();
        }
    }

    showQuoteButtonIfNeeded() {
        console.log('CommentManager: showQuoteButtonIfNeeded called');
        console.log('CommentManager: Selected text length:', this.selectedText.length);
        console.log('CommentManager: Last valid selection length:', this.lastValidSelection.length);
        console.log('CommentManager: Active textarea:', !!this.activeTextarea);
        
        // Use lastValidSelection if current selection is empty but we have a valid one stored
        const textToUse = this.selectedText.length >= 10 ? this.selectedText : this.lastValidSelection;
        
        if (!textToUse || textToUse.length < 10) {
            console.log('CommentManager: No valid text to quote, hiding button');
            this.hideQuoteButton();
            return;
        }

        if (!this.activeTextarea) {
            console.log('CommentManager: No active textarea');
            return;
        }

        console.log('CommentManager: Creating quote button with text:', textToUse.substring(0, 30) + '...');
        this.createQuoteButton();
    }

    createQuoteButton() {
        // Remove existing button
        this.hideQuoteButton();

        // Find or create textarea container
        let container = this.activeTextarea.closest('.textarea-container');
        if (!container) {
            // Wrap textarea in container if it doesn't exist
            const wrapper = document.createElement('div');
            wrapper.className = 'textarea-container';
            this.activeTextarea.parentNode.insertBefore(wrapper, this.activeTextarea);
            wrapper.appendChild(this.activeTextarea);
            container = wrapper;
        }

        // Create quote button
        this.quoteButton = document.createElement('button');
        this.quoteButton.type = 'button';
        this.quoteButton.className = 'quote-button';
        this.quoteButton.innerHTML = '📝';
        this.quoteButton.title = 'Quote selected text';
        
        // Position button
        container.style.position = 'relative';
        
        // Add click handler
        this.quoteButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.insertCitation();
        });

        container.appendChild(this.quoteButton);
    }

    hideQuoteButton() {
        if (this.quoteButton) {
            this.quoteButton.remove();
            this.quoteButton = null;
        }
    }

    insertCitation() {
        // Use lastValidSelection if current selection is empty
        const textToUse = this.selectedText.length >= 10 ? this.selectedText : this.lastValidSelection;
        
        if (!textToUse || !this.activeTextarea) {
            console.log('CommentManager: No text to cite or no active textarea');
            return;
        }

        // Truncate to 200 characters
        let citation = textToUse;
        if (citation.length > 200) {
            citation = citation.substring(0, 197) + '...';
        }

        // Format as citation
        const citationText = `> ${citation}\n\n`;
        
        console.log('CommentManager: Inserting citation:', citationText);
        
        // Insert at cursor position
        this.insertAtCursor(this.activeTextarea, citationText);
        
        // Hide quote button and clear selections
        this.hideQuoteButton();
        this.selectedText = '';
        this.lastValidSelection = '';
        
        // Clear browser selection
        if (window.getSelection) {
            window.getSelection().removeAllRanges();
        }
        
        // Focus back to textarea
        this.activeTextarea.focus();
    }

    insertAtCursor(textarea, text) {
        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        const beforeText = textarea.value.substring(0, startPos);
        const afterText = textarea.value.substring(endPos);
        
        textarea.value = beforeText + text + afterText;
        
        // Set cursor position after inserted text
        const newPos = startPos + text.length;
        textarea.setSelectionRange(newPos, newPos);
        
        // Trigger input event for any listeners
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    startEdit(button) {
        const commentEl = button.closest('[data-comment-id]');
        if (!commentEl) return;

        const commentId = commentEl.getAttribute('data-comment-id');
        const textDisplay = commentEl.querySelector('.comment-text-display');
        const editForm = commentEl.querySelector('.comment-edit-form');
        const editBtn = commentEl.querySelector('.edit-comment-btn');

        if (!textDisplay || !editForm) return;

        // Hide text display and edit button
        textDisplay.style.display = 'none';
        editBtn.style.display = 'none';

        // Show edit form
        editForm.style.display = 'block';

        // Focus on textarea and position cursor at end
        const textarea = editForm.querySelector('.comment-edit-textarea');
        if (textarea) {
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }

        // Add editing class for styling
        commentEl.classList.add('comment-editing');
    }

    async saveComment(button) {
        const commentEl = button.closest('[data-comment-id]');
        if (!commentEl) return;

        const commentId = commentEl.getAttribute('data-comment-id');
        const textarea = commentEl.querySelector('.comment-edit-textarea');
        const saveBtn = button;

        if (!textarea) return;

        const content = textarea.value.trim();

        if (content === '') {
            this.showError(commentEl, 'Kommentarinhalt darf nicht leer sein.');
            return;
        }

        // Show loading state
        saveBtn.disabled = true;
        saveBtn.textContent = 'Speichern...';

        try {
            const formData = new FormData();
            formData.append('comment_id', commentId);
            formData.append('content', content);
            formData.append('ajax', '1');

            const response = await fetch('edit_comment_process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.updateCommentDisplay(commentEl, result.data);
                this.cancelEdit(button, false); // Don't restore original content
                this.showSuccess(commentEl, result.message);
            } else {
                this.showError(commentEl, result.message);
            }
        } catch (error) {
            console.error('Error saving comment:', error);
            this.showError(commentEl, 'Fehler beim Speichern des Kommentars.');
        } finally {
            // Restore button state
            saveBtn.disabled = false;
            saveBtn.textContent = 'Speichern';
        }
    }

    cancelEdit(button, restoreContent = true) {
        const commentEl = button.closest('[data-comment-id]');
        if (!commentEl) return;

        const textDisplay = commentEl.querySelector('.comment-text-display');
        const editForm = commentEl.querySelector('.comment-edit-form');
        const editBtn = commentEl.querySelector('.edit-comment-btn');
        const textarea = commentEl.querySelector('.comment-edit-textarea');

        if (!textDisplay || !editForm) return;

        // Restore original content if needed
        if (restoreContent && textarea) {
            const originalText = commentEl.querySelector('.comment-text').textContent;
            textarea.value = originalText;
        }

        // Show text display and edit button
        textDisplay.style.display = 'block';
        if (editBtn) editBtn.style.display = 'inline-block';

        // Hide edit form
        editForm.style.display = 'none';

        // Remove editing class
        commentEl.classList.remove('comment-editing');

        // Clear any error messages
        this.clearMessages(commentEl);
    }

    async deleteComment(button) {
        const commentEl = button.closest('[data-comment-id]');
        if (!commentEl) return;

        const commentId = commentEl.getAttribute('data-comment-id');

        // Confirm deletion
        if (!confirm('Möchten Sie diesen Kommentar wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
            return;
        }

        // Show loading state
        button.disabled = true;
        button.textContent = 'Löschen...';

        try {
            const formData = new FormData();
            formData.append('comment_id', commentId);
            formData.append('ajax', '1');

            const response = await fetch('delete_comment_process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Animate removal
                commentEl.style.transition = 'opacity 0.3s ease-out';
                commentEl.style.opacity = '0';
                
                setTimeout(() => {
                    commentEl.remove();
                    this.showGlobalSuccess('Kommentar erfolgreich gelöscht.');
                }, 300);
            } else {
                this.showError(commentEl, result.message);
            }
        } catch (error) {
            console.error('Error deleting comment:', error);
            this.showError(commentEl, 'Fehler beim Löschen des Kommentars.');
        } finally {
            // Restore button state
            button.disabled = false;
            button.textContent = 'Löschen';
        }
    }

    updateCommentDisplay(commentEl, data) {
        const textEl = commentEl.querySelector('.comment-text');
        const editedIndicator = commentEl.querySelector('.edited-indicator');

        if (textEl) {
            textEl.innerHTML = data.content;
        }

        // Add/update edited indicator
        if (data.is_edited) {
            if (!editedIndicator) {
                const metaEl = commentEl.querySelector('.comment-meta strong');
                if (metaEl) {
                    metaEl.insertAdjacentHTML('afterend', '<small class="edited-indicator text-muted ms-2">bearbeitet</small>');
                }
            }
        }
    }

    showError(commentEl, message) {
        this.clearMessages(commentEl);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger mt-2 comment-error';
        errorDiv.textContent = message;
        commentEl.appendChild(errorDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }

    showSuccess(commentEl, message) {
        this.clearMessages(commentEl);
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success mt-2 comment-success';
        successDiv.textContent = message;
        commentEl.appendChild(successDiv);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 3000);
    }

    showGlobalSuccess(message) {
        // Try to show in main container or create temporary notification
        let container = document.querySelector('.container-fluid') || document.querySelector('.container') || document.body;
        
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show';
        successDiv.style.position = 'fixed';
        successDiv.style.top = '20px';
        successDiv.style.right = '20px';
        successDiv.style.zIndex = '9999';
        successDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.appendChild(successDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 5000);
    }

    startReply(button) {
        const commentEl = button.closest('[data-comment-id]');
        if (!commentEl) return;

        const replyForm = commentEl.querySelector('.comment-reply-form');
        const replyBtn = commentEl.querySelector('.reply-comment-btn');

        if (!replyForm) return;

        // Hide reply button
        replyBtn.style.display = 'none';

        // Show reply form
        replyForm.style.display = 'block';

        // Focus on textarea and set initial content
        const textarea = replyForm.querySelector('.comment-reply-textarea');
        if (textarea) {
            const username = button.dataset.username;
            if (username) {
                textarea.value = `@${username} `;
            }
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }

        // Add replying class for styling
        commentEl.classList.add('comment-replying');
    }

    async saveReply(button) {
        const commentEl = button.closest('[data-comment-id]');
        if (!commentEl) return;

        const textarea = commentEl.querySelector('.comment-reply-textarea');
        const saveBtn = button;

        if (!textarea) return;

        const content = textarea.value.trim();

        if (content === '') {
            this.showError(commentEl, 'Antwortinhalt darf nicht leer sein.');
            return;
        }

        // Show loading state
        saveBtn.disabled = true;
        saveBtn.textContent = 'Speichern...';

        try {
            // Get current post ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const postId = urlParams.get('id');

            const formData = new FormData();
            formData.append('comment_content', content);
            formData.append('post_id', postId);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                // Reload page to show new comment
                window.location.reload();
            } else {
                this.showError(commentEl, 'Fehler beim Speichern der Antwort.');
            }
        } catch (error) {
            console.error('Error saving reply:', error);
            this.showError(commentEl, 'Fehler beim Speichern der Antwort.');
        } finally {
            // Restore button state
            saveBtn.disabled = false;
            saveBtn.textContent = 'Antwort speichern';
        }
    }

    cancelReply(button) {
        const commentEl = button.closest('[data-comment-id]');
        if (!commentEl) return;

        const replyForm = commentEl.querySelector('.comment-reply-form');
        const replyBtn = commentEl.querySelector('.reply-comment-btn');
        const textarea = commentEl.querySelector('.comment-reply-textarea');

        if (!replyForm) return;

        // Clear textarea
        if (textarea) {
            textarea.value = '';
        }

        // Show reply button
        if (replyBtn) replyBtn.style.display = 'inline-block';

        // Hide reply form
        replyForm.style.display = 'none';

        // Remove replying class
        commentEl.classList.remove('comment-replying');

        // Clear any error messages
        this.clearMessages(commentEl);
    }

    clearMessages(commentEl) {
        const messages = commentEl.querySelectorAll('.comment-error, .comment-success');
        messages.forEach(msg => msg.remove());
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('CommentManager: Initializing...');
    window.commentManager = new CommentManager();
    console.log('CommentManager: Initialized successfully');
});

// Also provide global access for manual initialization
window.CommentManager = CommentManager;