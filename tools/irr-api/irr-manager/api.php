<?php
/**
 * IRR Manager API Proxy & Data Handler
 * Designed for PHP 7.4
 */

header('Content-Type: application/json');

// Use paths relative to this script
$dataDir = 'data/';
$logDir = 'logs/';

// Ensure directories exist and are writable
foreach ([$dataDir, $logDir] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        $fullPath = realpath($dir) ?: $dir;
        die(json_encode([
            'success' => false, 
            'message' => "O diretório '$dir' não existe ou não tem permissão de escrita. Por favor, execute: chmod 777 $dir",
            'path' => $fullPath
        ]));
    }
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_asns':
        $asns = [];
        $files = glob($dataDir . 'AS*.json');
        foreach ($files as $file) {
            $content = json_decode(file_get_contents($file), true);
            $asns[] = [
                'asn' => basename($file, '.json'),
                'name' => $content['asn_name'] ?? 'N/A',
                'object_count' => count($content['objects'] ?? [])
            ];
        }
        echo json_encode(['success' => true, 'data' => $asns]);
        break;

    case 'get_asn':
        $asn = $_GET['asn'] ?? '';
        $file = $dataDir . $asn . '.json';
        if (file_exists($file)) {
            echo file_get_contents($file);
        } else {
            echo json_encode(['success' => false, 'message' => 'ASN not found']);
        }
        break;

    case 'save_asn':
        $input = json_decode(file_get_contents('php://input'), true);
        $asn = $input['asn'] ?? '';
        if (!$asn) {
            echo json_encode(['success' => false, 'message' => 'Invalid ASN']);
            break;
        }
        
        $file = $dataDir . $asn . '.json';
        $currentData = file_exists($file) ? json_decode(file_get_contents($file), true) : ['asn' => $asn, 'objects' => []];
        $currentData['asn_name'] = $input['asn_name'] ?? $currentData['asn_name'] ?? '';
        $currentData['mnt_by'] = $input['mnt_by'] ?? $currentData['mnt_by'] ?? '';
        $currentData['mntner_password'] = $input['mntner_password'] ?? $currentData['mntner_password'] ?? '';
        
        if (isset($input['objects'])) {
            $currentData['objects'] = $input['objects'];
        }
        
        if (file_put_contents($file, json_encode($currentData, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving file']);
        }
        break;

    case 'sync_whois':
        $asn = $_GET['asn'] ?? '';
        if (!$asn) {
            echo json_encode(['success' => false, 'message' => 'Invalid ASN']);
            break;
        }

        $file = $dataDir . $asn . '.json';
        if (!file_exists($file)) {
            echo json_encode(['success' => false, 'message' => 'ASN data not found']);
            break;
        }
        $currentData = json_decode(file_get_contents($file), true);
        
        // Execute WHOIS by ASN
        $cmdAsn = "whois -h rr.tc.br \"$asn\"";
        exec($cmdAsn, $outputAsn, $returnCodeAsn);
        $rawAsn = implode("\n", $outputAsn);
        $objects = parseRpsl($rawAsn);
        
        // Optionally search by Maintainer if provided
        $mnt = $currentData['mnt_by'] ?? '';
        if ($mnt) {
            $cmdMnt = "whois -h rr.tc.br -i mnt-by \"$mnt\"";
            exec($cmdMnt, $outputMnt, $returnCodeMnt);
            $rawMnt = implode("\n", $outputMnt);
            $objectsMnt = parseRpsl($rawMnt);
            
            // Merge unique objects (simple name/type comparison)
            $existingNames = array_map(function($o) { return $o['type'] . '|' . $o['name']; }, $objects);
            foreach ($objectsMnt as $obj) {
                if (!in_array($obj['type'] . '|' . $obj['name'], $existingNames)) {
                    $objects[] = $obj;
                }
            }
        }
        
        foreach ($objects as &$obj) {
            $obj['status'] = 'sincronizado';
        }
        
        $currentData['objects'] = $objects;
        
        if (file_put_contents($file, json_encode($currentData, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'count' => count($objects)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving file']);
        }
        break;

    case 'submit_to_tc':
        $input = json_decode(file_get_contents('php://input'), true);
        $asn = $input['asn'] ?? '';
        $objectIndex = $input['index'] ?? -1;
        
        $file = $dataDir . $asn . '.json';
        if (!file_exists($file)) {
            echo json_encode(['success' => false, 'message' => 'ASN data not found']);
            break;
        }
        
        $asnData = json_decode(file_get_contents($file), true);
        if ($objectIndex < 0 || !isset($asnData['objects'][$objectIndex])) {
            echo json_encode(['success' => false, 'message' => 'Object data not found']);
            break;
        }
        
        $obj = $asnData['objects'][$objectIndex];
        $password = $asnData['mntner_password'] ?? '';
        
        // Prepare IRRd JSON format
        $irrdPayload = [
            'objects' => [
                ['attributes' => $obj['attributes']]
            ],
            'passwords' => [$password]
        ];
        
        // Call TC API
        $response = callTcApi($irrdPayload);
        
        // Log response
        $timestamp = date('Ymd_His');
        $objName = $obj['name'] ?? 'object';
        file_put_contents($logDir . "{$timestamp}_{$asn}_{$objName}.json", json_encode([
            'request' => $irrdPayload,
            'response' => $response
        ], JSON_PRETTY_PRINT));
        
        echo json_encode($response);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Parses RPSL text into JSON objects
 */
function parseRpsl($text) {
    $objects = [];
    $rawObjects = explode("\n\n", str_replace("\r", "", $text));
    
    foreach ($rawObjects as $raw) {
        if (trim($raw) == "") continue;
        
        $lines = explode("\n", $raw);
        $attributes = [];
        $type = '';
        $name = '';
        
        $currentName = '';
        $currentValue = '';
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || trim($line) == "") continue;
            
            // Strip inline comments for parsing, but usually values might contain # in some cases
            // Standard RPSL says # is a comment.
            $cleanLine = preg_replace('/\s+#.*$/', '', $line);
            
            // Handle line continuation (starts with space)
            if (isset($cleanLine[0]) && $cleanLine[0] === ' ') {
                if ($currentName) {
                    $currentValue .= "\n" . trim($cleanLine);
                    // Update last added attribute
                    $attributes[count($attributes)-1]['value'] = $currentValue;
                }
                continue;
            }
            
            if (preg_match('/^([a-z0-9-]+):\s*(.*)$/i', $cleanLine, $matches)) {
                $currentName = strtolower($matches[1]);
                $currentValue = trim($matches[2]);
                
                // Identify object type from first attribute usually
                if (empty($type)) {
                    $type = $currentName;
                    $name = $currentValue;
                }
                
                $attributes[] = [
                    'name' => $currentName,
                    'value' => $currentValue
                ];
            }
        }
        
        if (!empty($attributes)) {
            $objects[] = [
                'type' => $type,
                'name' => $name,
                'attributes' => $attributes,
                'status' => 'sincronizado'
            ];
        }
    }
    
    return $objects;
}

/**
 * Calls the TC IRRd API
 */
function callTcApi($payload) {
    $url = 'https://bgp.net.br/api/v1/objects'; // Assuming IRRd API endpoint, checking requirement "https://bgp.net.br/ (onde tá descrio irr.tc.br)"
    // The user said: https://bgp.net.br/ (where irr.tc.br is described).
    // Usually IRRd API is at /v1/objects/ or similar. I'll use a placeholder if not sure, 
    // but the payload format was given.
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => $error];
    }
    
    return [
        'success' => $httpCode == 200,
        'http_code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}
