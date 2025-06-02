
<?php

class LanguageHandler {
    private static $instance = null;
    private $translations = [];
    private $currentLang;

    private function __construct() {
        $this->currentLang = getCurrentLanguage();
        $this->loadTranslations();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadTranslations() {
        // Try to load current language
        $langFile = __DIR__ . "/../languages/{$this->currentLang}/frontend.php";
        
        if (file_exists($langFile)) {
            $this->translations = require $langFile;
        } else {
            // Fallback to German
            $this->translations = require __DIR__ . "/../languages/de/frontend.php";
        }
    }

    public function translate($key, $placeholders = [], $fallback = '') {
        $translation = $this->translations[$key] ?? $fallback ?: $key;
        
        // Replace placeholders if any
        foreach ($placeholders as $placeholder => $value) {
            $translation = str_replace(":$placeholder", $value, $translation);
        }
        
        return $translation;
    }

    public function getCurrentLang() {
        return $this->currentLang;
    }
}

// Helper function for easier translation
function __($key, $placeholders = [], $fallback = '') {
    return LanguageHandler::getInstance()->translate($key, $placeholders, $fallback);
}