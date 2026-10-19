<?php

require_once __DIR__ . '/../../config/config.php';

/**
 * Validates if a string is valid JSON.
 * Returns the decoded array/object if valid, or null if invalid.
 */
function projectsValidateJson(?string $jsonString) {
    if (empty(trim($jsonString))) {
        return null; // Empty is treated as valid NULL in DB
    }
    
    $decoded = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("Invalid JSON format provided.");
    }
    
    return json_encode($decoded); // Re-encode to ensure clean DB storage
}

/**
 * Pretty-prints JSON for the textarea editors so it's readable.
 */
function projectsPrettyJson(?string $jsonString): string {
    if (empty($jsonString)) return '';
    $decoded = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) return $jsonString;
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}