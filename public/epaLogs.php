<?php
/**
 * Author: Jacob Christensen
 * Description: Demo parsing an Access log file
 * Made the very simple way ;)
 */
require_once '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Autoloader.php';
Autoloader::register();

$logFileDir = '..' . DIRECTORY_SEPARATOR .'log_files' . DIRECTORY_SEPARATOR;

$files = [
    (object) ['prefix' => '1995-08', 'name' => $logFileDir . 'epa-http.txt']
];

$db = new DbDriver('..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'database.db');

// Check for loaded data
$loadedFiles = $db->fetchLogFiles();
foreach ($files as $key => $file) {
    if (in_array($file->name, $loadedFiles)) {
        unset($files[$key]);
    }
}
// If no loaded data we attempt to read it
if (!empty($files)) {
    $parser = new Parser();
    foreach ($files as $key => $file) {
        $data = $parser->parse($file->prefix, $file->name);
        $db->insertLogfile($file->name);
        if (!empty($data)) {
            $db->insertData($data);
        }
    }
}

$result = [
    'methods'   => $db->fetchRequestMethods(),
    'codes'     => $db->fetchRequestResponseCodes(),
    'per_minute'=> $db->fetchRequestPerPeriod(),
    'avg_size'  => $db->fetchRequestAnswerSize(),
];

Response::return($result, Response::HTTP_OK);
