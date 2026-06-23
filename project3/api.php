<?php
require_once 'config.php';

// Get the request method and resource
$method = $_SERVER['REQUEST_METHOD'];
$resource = isset($_GET['resource']) ? $_GET['resource'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Read JSON input for POST/PUT
$input = json_decode(file_get_contents('php://input'), true);

function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function handleError($message, $status = 400) {
    sendResponse(['error' => $message], $status);
}

// ------------------- ROUTING -------------------

switch ($resource) {
    case 'customers':
        handleCustomers($method, $id, $input);
        break;
    case 'orders':
        handleOrders($method, $id, $input);
        break;
    default:
        handleError('Invalid resource. Use "customers" or "orders".', 404);
}

// ------------------- CUSTOMERS CRUD -------------------

function handleCustomers($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
                $stmt->execute([$id]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$data) handleError('Customer not found', 404);
                sendResponse($data);
            } else {
                $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            if (empty($input['name']) || empty($input['email'])) {
                handleError('Name and email are required');
            }
            // Check for duplicate email
            $check = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
            $check->execute([$input['email']]);
            if ($check->fetch()) handleError('Email already registered', 409);

            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
            $stmt->execute([$input['name'], $input['email'], $input['phone'] ?? null]);
            sendResponse(['id' => $pdo->lastInsertId(), 'message' => 'Customer created'], 201);
            break;

        case 'PUT':
            if (!$id) handleError('ID required for update');
            $fields = [];
            $params = [];
            if (isset($input['name'])) { $fields[] = "name = ?"; $params[] = $input['name']; }
            if (isset($input['email'])) { 
                // Check if new email is already used by another customer
                $check = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
                $check->execute([$input['email'], $id]);
                if ($check->fetch()) handleError('Email already registered', 409);
                $fields[] = "email = ?"; 
                $params[] = $input['email']; 
            }
            if (isset($input['phone'])) { $fields[] = "phone = ?"; $params[] = $input['phone']; }
            if (empty($fields)) handleError('No fields to update');

            $params[] = $id;
            $sql = "UPDATE customers SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            sendResponse(['message' => 'Customer updated']);
            break;

        case 'DELETE':
            if (!$id) handleError('ID required for delete');
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) handleError('Customer not found', 404);
            sendResponse(['message' => 'Customer deleted']);
            break;

        default:
            handleError('Method not allowed', 405);
    }
}

// ------------------- ORDERS CRUD -------------------

function handleOrders($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("
                    SELECT o.*, c.name as customer_name 
                    FROM orders o
                    LEFT JOIN customers c ON o.customer_id = c.id
                    WHERE o.id = ?
                ");
                $stmt->execute([$id]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$data) handleError('Order not found', 404);
                sendResponse($data);
            } else {
                $stmt = $pdo->query("
                    SELECT o.*, c.name as customer_name 
                    FROM orders o
                    LEFT JOIN customers c ON o.customer_id = c.id
                    ORDER BY o.id DESC
                ");
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            if (empty($input['customer_id']) || empty($input['product']) || empty($input['quantity']) || empty($input['price'])) {
                handleError('customer_id, product, quantity, and price are required');
            }
            // Verify customer exists
            $check = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
            $check->execute([$input['customer_id']]);
            if (!$check->fetch()) handleError('Customer not found', 404);

            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, product, quantity, price, status) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['customer_id'],
                $input['product'],
                $input['quantity'],
                $input['price'],
                $input['status'] ?? 'pending'
            ]);
            sendResponse(['id' => $pdo->lastInsertId(), 'message' => 'Order created'], 201);
            break;

        case 'PUT':
            if (!$id) handleError('ID required for update');
            $fields = [];
            $params = [];
            if (isset($input['customer_id'])) { 
                $check = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
                $check->execute([$input['customer_id']]);
                if (!$check->fetch()) handleError('Customer not found', 404);
                $fields[] = "customer_id = ?"; 
                $params[] = $input['customer_id']; 
            }
            if (isset($input['product'])) { $fields[] = "product = ?"; $params[] = $input['product']; }
            if (isset($input['quantity'])) { $fields[] = "quantity = ?"; $params[] = $input['quantity']; }
            if (isset($input['price'])) { $fields[] = "price = ?"; $params[] = $input['price']; }
            if (isset($input['status'])) { $fields[] = "status = ?"; $params[] = $input['status']; }
            if (empty($fields)) handleError('No fields to update');

            $params[] = $id;
            $sql = "UPDATE orders SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            sendResponse(['message' => 'Order updated']);
            break;

        case 'DELETE':
            if (!$id) handleError('ID required for delete');
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) handleError('Order not found', 404);
            sendResponse(['message' => 'Order deleted']);
            break;

        default:
            handleError('Method not allowed', 405);
    }
}
?>