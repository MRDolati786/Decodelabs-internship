<?php
// Use __DIR__ to get the absolute path
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Validation.php';

$db = new Database();
$validation = new Validation();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            getItemById($db, $id);
        } else {
            getAllItems($db);
        }
        break;
    case 'POST':
        createItem($db, $validation);
        break;
    case 'PUT':
        if ($id) {
            updateItem($db, $validation, $id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bad Request', 'message' => 'ID required for update']);
        }
        break;
    case 'DELETE':
        if ($id) {
            deleteItem($db, $id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bad Request', 'message' => 'ID required for delete']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        break;
}

// ---------- Functions ----------
function getAllItems($db) {
    $items = $db->getAllItems();
    http_response_code(200);
    echo json_encode(['success' => true, 'count' => count($items), 'data' => $items]);
}

function getItemById($db, $id) {
    $item = $db->getItemById($id);
    if ($item) {
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $item]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Not Found', 'message' => "Item ID $id not found"]);
    }
}

function createItem($db, $validation) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    $errors = $validation->validateItem($input);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Validation Error', 'details' => $errors]);
        return;
    }
    $newItem = $db->createItem($input);
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Item created', 'data' => $newItem]);
}

function updateItem($db, $validation, $id) {
    if (!$db->getItemById($id)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Not Found', 'message' => "Item ID $id not found"]);
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    $errors = $validation->validateItem($input);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Validation Error', 'details' => $errors]);
        return;
    }
    $updated = $db->updateItem($id, $input);
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Item updated', 'data' => $updated]);
}

function deleteItem($db, $id) {
    if (!$db->getItemById($id)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Not Found', 'message' => "Item ID $id not found"]);
        return;
    }
    $deleted = $db->deleteItem($id);
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Item deleted', 'data' => $deleted]);
}
?>