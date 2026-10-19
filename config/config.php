<?php

// Automatically detect if running inside /pingaksh-admin subfolder or root domain
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$detectedBase = (strpos($scriptName, '/pingaksh-admin/') === 0) ? '/pingaksh-admin' : '';

define('BASE_URL', $detectedBase);