
<footer class="footer mt-5">
<div class="footer-top mb-5"></div>
    <div class="container">
        <div class="row">
            <!-- Logo Column -->
            <div class="col-md-4 order-md-2 text-center text-md-end mb-4 mb-md-0">
                <div class="footer-logo">
                    <h3 class="logo">EMPIRO</h3>
                    <h4 class="logo">MEINE ERFAHRUNG <br> MIT PSYCHOTHERAPEUTEN* <br> IN DER SCHWEIZ</h4>
                </div>
            </div>

            <!-- Links Column -->
            
            <div class="col-md-8 order-md-1">
                <div class="row">
                    <!-- Navigation Links -->
                    <div class="col-sm-6 mb-4">
                        <h5 class="mb-3">Navigation</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>forum.php"><?= __('nav_forum') ?></a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>guidelines.php"><?= __('nav_guidelines') ?></a>
                            </li>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <li class="mb-2">
                                    <a href="<?php echo BASE_URL; ?>login.php"><?= __('nav_login') ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Additional Links -->
                    <div class="col-sm-6 mb-4">
                        <h5 class="mb-3">Weiteres</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>impressum.php">Impressum</a>
                            </li>
                            <li class="mb-2">
                                <button type="button" 
                                        class="btn btn-feedback" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#feedbackModal">
                                    Feedback
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="feedbackForm" action="send_feedback.php" method="POST">
                    <div class="mb-3">
                        <label for="feedbackText" class="form-label">Ihr Feedback</label>
                        <textarea class="form-control" id="feedbackText" name="feedback" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Senden</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.footer {
    color: var(--primary);
    background-color: none;
    padding: 0rem 0;
    margin-top: 3rem;
}

/* .footer-top {
    width: 100%;
    height: 30px;
    border-bottom-left-radius: 25px;
    border-bottom-right-radius: 25px;
    background: #ffd662ad;
} */

.footer-logo {
    margin-bottom: 1rem;
}

.footer-logo .logo {
    margin: 0;
    font-size: 1.5rem;
    line-height: 1.2;
}

.footer-logo h4.logo {
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.footer h5 {
    font-weight: 600;
}

.footer ul li a {
    text-decoration: none;
    transition: color 0.2s;
}

.footer ul li a:hover {
    text-decoration: none;
}

.btn-feedback {
    padding: 0;
    background: none;
    border: none;
    text-align: left;
    transition: color 0.2s;
}

.btn-feedback:hover {
}

/* Modal Styles */
.modal-content {
    border-radius: 0.5rem;
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.modal-title {
    font-weight: 600;
}

/* Responsive Adjustments */
@media (max-width: 767px) {
    .footer {
        padding: 0rem 0;
        text-align: center;
    }

    .footer-logo {
        margin-bottom: 2rem;
    }

    .footer ul {
        margin-bottom: 2rem;
    }

    .btn-feedback {
        display: inline-block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.getElementById('feedbackForm');
    
    feedbackForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Vielen Dank für Ihr Feedback!');
                $('#feedbackModal').modal('hide');
                feedbackForm.reset();
            } else {
                alert('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.');
        });
    });
});
</script>

