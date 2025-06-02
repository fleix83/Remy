<?php
// ai_handler.php

header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/ai_config.php';

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    $input = file_get_contents('php://input');
    error_log("Received input: " . $input);
    
    $data = json_decode($input, true);
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }

    // Format the posts data
    $postsContext = "";
    if (isset($data['therapistData']['posts'])) {
        foreach ($data['therapistData']['posts'] as $post) {
            $postsContext .= "Title: " . $post['title'] . "\n";
            $postsContext .= "Content: " . $post['content'] . "\n\n";
        }
    }

    // Format the messages exactly as in the successful curl request
    $requestData = [
        "model" => "claude-3-opus-20240229",
        "max_tokens" => 1024,
        "messages" => [
            [
                "role" => "user",
                "content" => "Based on these reviews, please analyze and answer the following question in German:\n\n" .
                            "Question: " . $data['query'] . "\n\n" .
                            "Reviews:\n" . $postsContext
            ]
        ]
    ];

    error_log("Sending request to Claude API: " . json_encode($requestData));

    $ch = curl_init(CLAUDE_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'anthropic-version: 2023-06-01',
            'x-api-key: ' . CLAUDE_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    error_log("Claude API response code: " . $httpCode);
    error_log("Claude API response: " . $response);

    if ($httpCode !== 200) {
        throw new Exception("API request failed with status code: " . $httpCode . "\nResponse: " . $response);
    }

    $responseData = json_decode($response, true);
    
    if (!isset($responseData['content'][0]['text'])) {
        throw new Exception('Unexpected API response format');
    }

    echo json_encode([
        'success' => true,
        'response' => $responseData['content'][0]['text']
    ]);

} catch (Exception $e) {
    error_log("Error in AI handler: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


