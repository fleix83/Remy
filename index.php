<?php 
require_once 'includes/init.php'; 
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/language_utils.php'; 
?>

<style>
    body {
    height: 100vh;
    }
</style>

<body>

<div class="d-flex justify-content-end">
<a href="forum.php" class="btn btn-outline-success btn-lg btn-forum m-2">Forum</a>
</div>


    <div class="index-container">
        <div class="row">
            <div class="col-md-8">  
                <p class="intro">
                    <!-- <span class="highlite">Meine Erfahrung</span> ist ein <span class="highlite">Austauschforum</span> für <span class="highlite">Patient:innen</span> von <span class="highlite">Psycholog:innen</span>, <span class="highlite">Psychiater:innen</span>, <span class="highlite">Therapeut:innen</span> verwandter Fachrichtungen und <span class="highlite">Institutionen</span>.  -->
                    <span class="highlite">Empiro</span> ist das Austauschforum für Patienten* von Psychologen*, Psychiater*, Therapeuten* verwandter Fachrichtungen und Institutionen.
                </p>
            </div>  
        </div> 
        <main class="landing-button-wrapper">
            <div class="row ">
                <div class="col-md-6">
                    <a href="login.php" class="btn btn-primary btn-lg btn-login m-2">Anmelden</a>
                    <a href="register.php" class="btn btn-secondary btn-lg btn-register m-2">Registrieren</a>
                </div>
            </div>
        </main>
    </div> 

  

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>

