<?php
/**
 * HTML Purifier Configuration
 * 
 * Creates a strict HTMLPurifier configuration that strips external formatting
 * while preserving basic text formatting suitable for the webapp's styling.
 */

function getStrictHtmlPurifierConfig() {
    $config = HTMLPurifier_Config::createDefault();
    
    // Strip all style attributes to remove inline CSS from external sources
    $config->set('CSS.AllowedProperties', array());
    
    // Strip all class attributes to remove external CSS classes
    $config->set('Attr.AllowedClasses', array());
    
    // Only allow basic HTML tags without formatting attributes
    $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],ul,ol,li,blockquote');
    
    // Remove any attributes that could carry external formatting
    $config->set('HTML.AllowedAttributes', 'a.href');
    
    // Ensure blockquotes don't carry external styling
    $config->set('HTML.AllowedElements', array('p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li', 'blockquote'));
    
    // Remove any font or text styling elements
    $config->set('HTML.ForbiddenElements', array('font', 'span', 'div', 'style', 'script'));
    
    // Clean up whitespace and normalize HTML structure
    $config->set('HTML.TidyLevel', 'heavy');
    $config->set('Output.TidyFormat', true);
    
    return $config;
}

/**
 * Purify content with strict formatting removal
 * 
 * @param string $content The HTML content to purify
 * @return string The cleaned content with external formatting removed
 */
function purifyContentStrict($content) {
    require_once __DIR__ . '/../vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
    
    $config = getStrictHtmlPurifierConfig();
    $purifier = new HTMLPurifier($config);
    
    return $purifier->purify($content);
}