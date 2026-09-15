<?php
/**
 * Backend API en PHP para Cátedra San Jerónimo 2026 en cPanel / Apache.
 * Permite guardar e interconectar inscripciones en tiempo real entre celulares, PCs y Panel Admin.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db_file = __DIR__ . '/inscripciones.json';

function load_db($db_file) {
    if (!file_exists($db_file)) {
        return [];
    }
    $content = @file_get_contents($db_file);
    if (!$content) return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function save_db($db_file, $data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return @file_put_contents($db_file, $json, LOCK_EX) !== false;
}

$uri = $_SERVER['REQUEST_URI'];
$endpoint = $_GET['endpoint'] ?? '';

if (!$endpoint) {
    $parsed_path = parse_url($uri, PHP_URL_PATH);
    if (strpos($parsed_path, '/api/') !== false) {
        $parts = explode('/api/', $parsed_path);
        $endpoint = end($parts);
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(load_db($db_file), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $raw_input = file_get_contents('php://input');
    $body = json_decode($raw_input, true) ?? [];
    $db = load_db($db_file);

    // 1. Nueva inscripción
    if ($endpoint === 'inscripciones' || strpos($uri, 'inscripciones') !== false) {
        $nombres = mb_strtoupper(trim($body['nombres'] ?? ''), 'UTF-8');
        $dni = trim($body['dni'] ?? '');
        $celular = trim($body['celular'] ?? '');
        $institucion = mb_strtoupper(trim($body['institucion'] ?? ''), 'UTF-8');

        if (!$nombres || !$dni) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'datos_incompletos'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar duplicado de DNI
        foreach ($db as $item) {
            if (strval($item['dni'] ?? '') === $dni) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'error' => 'duplicate_dni',
                    'existente' => $item
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $code_num = count($db) + 1;
        $nuevo_codigo = sprintf('CSJ-2026-%04d', $code_num);
        date_default_timezone_set('America/Lima');
        $nuevo_registro = [
            'codigo' => $nuevo_codigo,
            'nombres' => $nombres,
            'dni' => $dni,
            'celular' => $celular ? $celular : '900000000',
            'institucion' => $institucion ? $institucion : 'SAN JERÓNIMO',
            'asistencia' => 'Ambos días (21 y 22 de setiembre)',
            'attDay1' => false,
            'attDay2' => false,
            'registradoEl' => date('d/m/Y, h:i:s A')
        ];

        $db[] = $nuevo_registro;
        save_db($db_file, $db);

        http_response_code(201);
        echo json_encode(['success' => true, 'data' => $nuevo_registro], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Actualizar Asistencia
    if ($endpoint === 'asistencia' || strpos($uri, 'asistencia') !== false) {
        $codigo = strtolower(trim($body['codigo'] ?? ''));
        $field = trim($body['field'] ?? '');
        $val = $body['val'] ?? null;

        $found = false;
        foreach ($db as &$item) {
            $item_code = strtolower(trim($item['codigo'] ?? ''));
            $item_dni = trim($item['dni'] ?? '');
            if ($item_code === $codigo || $item_dni === $codigo) {
                if ($field === 'attDay1' || $field === 'attDay2') {
                    $item[$field] = $val !== null ? (bool)$val : !($item[$field] ?? false);
                }
                if (isset($body['attDay1'])) $item['attDay1'] = (bool)$body['attDay1'];
                if (isset($body['attDay2'])) $item['attDay2'] = (bool)$body['attDay2'];
                $found = $item;
                break;
            }
        }

        if ($found) {
            save_db($db_file, $db);
            echo json_encode(['success' => true, 'data' => $found], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'no_encontrado'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // 3. Importación Masiva
    if ($endpoint === 'importar' || strpos($uri, 'importar') !== false) {
        $items = $body['items'] ?? [];
        $count = 0;
        date_default_timezone_set('America/Lima');
        foreach ($items as $item) {
            $nombres = mb_strtoupper(trim($item['nombres'] ?? ''), 'UTF-8');
            $dni = trim($item['dni'] ?? '');
            if ($nombres && $dni) {
                $exists = false;
                foreach ($db as $r) {
                    if (($r['dni'] ?? '') === $dni) { $exists = true; break; }
                }
                if (!$exists) {
                    $count++;
                    $code_num = count($db) + 1;
                    $db[] = [
                        'codigo' => sprintf('CSJ-2026-%04d', $code_num),
                        'nombres' => $nombres,
                        'dni' => $dni,
                        'celular' => trim($item['celular'] ?? '900000000'),
                        'institucion' => mb_strtoupper(trim($item['institucion'] ?? 'SAN JERÓNIMO'), 'UTF-8'),
                        'asistencia' => 'Ambos días (21 y 22 de setiembre)',
                        'attDay1' => false,
                        'attDay2' => false,
                        'registradoEl' => date('d/m/Y, h:i:s A')
                    ];
                }
            }
        }
        save_db($db_file, $db);
        echo json_encode(['success' => true, 'count' => $count, 'total' => count($db)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. Eliminar registro
    if ($endpoint === 'eliminar' || strpos($uri, 'eliminar') !== false) {
        $codigo = trim($body['codigo'] ?? '');
        $new_db = [];
        foreach ($db as $r) {
            if (($r['codigo'] ?? '') !== $codigo) {
                $new_db[] = $r;
            }
        }
        save_db($db_file, $new_db);
        echo json_encode(['success' => true, 'total' => count($new_db)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 5. Editar datos del participante
    if ($endpoint === 'editar' || strpos($uri, 'editar') !== false) {
        $codigo = trim($body['codigo'] ?? '');
        $nombres = mb_strtoupper(trim($body['nombres'] ?? ''), 'UTF-8');
        $dni = trim($body['dni'] ?? '');
        $celular = trim($body['celular'] ?? '');
        $institucion = mb_strtoupper(trim($body['institucion'] ?? ''), 'UTF-8');

        $found = null;
        foreach ($db as &$item) {
            if (strval($item['codigo'] ?? '') === $codigo) {
                if ($nombres !== '') $item['nombres'] = $nombres;
                if ($dni !== '') $item['dni'] = $dni;
                if ($celular !== '') $item['celular'] = $celular;
                if ($institucion !== '') $item['institucion'] = $institucion;
                $found = $item;
                break;
            }
        }

        if ($found) {
            save_db($db_file, $db);
            echo json_encode(['success' => true, 'data' => $found], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'no_encontrado'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

// Respuesta por defecto
echo json_encode(load_db($db_file), JSON_UNESCAPED_UNICODE);
