<?php

// Define API constants
define('CLAUDE_API_KEY', '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'); // Your actual Claude API key
define('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages');

// Optional: Test function to verify API key is loaded
function verifyApiConfig() {
    if (!defined('CLAUDE_API_KEY') || empty(CLAUDE_API_KEY)) {
        error_log('Claude API key is not configured properly');
        return false;
    }
    return true;
}