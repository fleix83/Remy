<?php
require_once 'includes/header.php';
require_once 'navbar.php';
?>

<main class="container my-5 p-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <h1 class="mb-5">Community Guidelines</h1>

            <section class="guideline-section mb-5">
                <h2 class="h3 mb-4">Schreibe aus der Ich-Perspektive</h2>
                <p class="mb-4">
                    Bitte schreibe aus Deiner persönlichen Perspektive. Die Wahrnehmung eines Menschen ist extrem subjektiv. 
                    Derselbe Mensch kann von verschiedenen Menschen komplett unterschiedlich wahrgenommen werden. Schreibe wie 
                    DU jemanden in bestimmten Situationen erlebt hast. Versuche genau zu sein. Bitte vermeide 
                    Verallgemeinerungen und wertende Zuschreibungen.
                </p>
                <div class="examples bg-light p-4 rounded mb-4">
                    <h3 class="h5 mb-3">Beispiele:</h3>
                    <div class="example mb-3">
                        <p class="text-danger mb-1"><strong>Statt:</strong> „Er war einfach inkompetent"</p>
                        <p class="text-success mb-1"><strong>Besser:</strong> „In meiner speziellen Situation (Situation ggf. beschreiben) konnte er mir leider nicht helfen."</p>
                    </div>
                    <div class="example">
                        <p class="text-danger mb-1"><strong>Statt:</strong> „Meine Therapeutin ist einfach super"</p>
                        <p class="text-success mb-1"><strong>Besser:</strong> „Meine Therapeutin hört mir meistens sehr gut zu und wiederholt manchmal, was ich gesagt habe. Dann merke ich, dass sie es wirklich verstanden hat."</p>
                    </div>
                </div>
            </section>

            <section class="guideline-section mb-5">
                <h2 class="h3 mb-4">Kritik an Therapeut:innen</h2>
                <p>
                    Auf dieser Plattform soll und darf kritisiert werden. Aber bitte formuliere konstruktiv, 
                    wertschätzend und respektiere den Menschen hinter dem Therapeuten. Beschimpfungen, Beleidigungen, 
                    Sexismus, Gewaltandrohungen und Verbreitung von falschen Tatsachen führen zur Sperrung.
                </p>
            </section>

            <section class="guideline-section mb-5">
                <h2 class="h3 mb-4">Persönliche Äusserungen von Therapeut:innen sind Tabu</h2>
                <p class="mb-4">
                    Persönliche Äusserungen von TherapeutInnen über ihre eigene private Person sind absolut tabu. 
                    Diese Plattform soll unter keinen Umständen dazu führen, dass sich TherapeutInnen in der Therapie 
                    persönlich zurückhalten. Wenn TherapeutInnen in der Therapie persönliches preisgeben, ist das sehr 
                    wertvoll und stärkt das Vertrauen zwischen PatientInnen und TherapeutInnen.
                </p>
                <div class="alert alert-warning">
                    <h4 class="h5 mb-3">Beispiele von sanktionierten Äusserungen:</h4>
                    <ul class="mb-0">
                        <li>Deine Therapeutin hat dir erzählt, dass ihre nahestehende Schwester Brustkrebs hat.</li>
                        <li>Dein Therapeut verrät dir, dass er selbst Anti-Depressiva nimmt.</li>
                    </ul>
                </div>
            </section>

            <section class="guideline-section mb-5">
                <h2 class="h3 mb-4">Namen von Therapeut:innen sind ausdrücklich erwünscht</h2>
                <p>
                    TherapeutInnen sollen namentlich genannt werden, idealerweise direkt im Titel. Es geht auf dieser 
                    Plattform ausdrücklich um Erfahrungen mit bestimmten Therapeut:innen. Ohne den Namen sind die Beiträge 
                    nur bedingt interessant. Bitte tagge in deinen Beiträgen auch den entsprechenden Kanton, in dem der/die 
                    Therapeut:in praktiziert.
                </p>
            </section>

            <section class="guideline-section mb-5">
                <h2 class="h3 mb-4">Anonymität/Privatsphäre</h2>
                <p class="mb-4">
                    Auf Empiro schreiben wir anonym unter einem Pseudonym. Therapie ist sehr privat, die Themen äusserst 
                    sensibel, die Therapien unterstehen der ärztlichen Schweigepflicht. Du bist bestimmst selber, wieviel du 
                    preisgeben möchtest.
                </p>
                <div class="card-header guidelines mb-3">
                        <h3 class="h5 mb-0">Gespeicherte Daten bei der Registrierung</h3>
                    </div>
                <div class="card guidelines mb-4">
                    <div class="card-body guidelines">
                        <ol class="mb-0">
                            <li>Usernamen/Pseudonym</li>
                            <li>Email-Adresse (Für maximale Anonymität verwende bitte eine anonyme Adresse)</li>
                            <li>IP-Adresse (Ist i.d.R. anonym, wenn du eine fixe IP-Adresse hast, weisst Du es)</li>
                            <li>Information, die in deinem Profil steht</li>
                        </ol>
                    </div>
                </div>
                <p class="text-muted">
                    Selbstverständlich geben wir keine Daten an Dritte weiter. Alle deine Daten können jederzeit von Dir 
                    selber aus dem System gelöscht werden.
                </p>
            </section>

            <section class="guideline-section mb-5">
                <h2 class="h3 mb-4">Lob</h2>
                <p>
                    Es ist für alle sehr wertvoll zu wissen, wenn Du positive Erfahrungen in einer Therapie gemacht hast. 
                    Wichtig ist wie immer genau zu sein: was genau war positiv und in welcher Situation. Positive 
                    Erfahrungen bedeuten auch nicht zwingend nur angenehme Situationen. Positiv kann auch sein, wenn Dein 
                    Therapeut gegen deinen Widerstand, deine Vermeidungsstrategien nicht locker gelassen hat, und dich so 
                    letztlich weitergebracht hat. Positives wird deshalb manchmal auch erst in der Rückschau sichtbar.
                </p>
            </section>
        </div>
    </div>
</main>
<?php require_once 'includes/footer.php'; ?>

<style>
.guideline-section {
    border-bottom: 1px solid #eee;
    padding-bottom: 2rem;
}

.guideline-section:last-child {
    border-bottom: none;
}

.example {
    padding-left: 1rem;
    border-left: 3px solid #dee2e6;
}

.h3 {
    color: #333;
    font-weight: 600;
}

.alert-warning {
    background-color: #ffffff;
    border: none;
}

.card {
    border-color: #dee2e6;
}

.card.guidelines {
    border: none;
}

.card-body.guidelines {
    border-radius: 15px;
    border: none;
}

.text-danger {
    color: #dc3545 !important;
}

.text-success {
    color: #28a745 !important;
}
</style>

