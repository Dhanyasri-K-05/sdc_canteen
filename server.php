<?php

/**
 * COMPLETE FIXED server.php
 * Run this with: php server.php
 */

require_once __DIR__ . '/config/database.php';

// WebSocket configuration
$host = '0.0.0.0';
$port = 8080;

// Create socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    die("Failed to create socket: " . socket_strerror(socket_last_error()) . "\n");
}

socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

if (!socket_bind($socket, $host, $port)) {
    die("Failed to bind socket: " . socket_strerror(socket_last_error()) . "\n");
}

if (!socket_listen($socket)) {
    die("Failed to listen on socket: " . socket_strerror(socket_last_error()) . "\n");
}

echo "✅ WebSocket server started on ws://{$host}:{$port}\n";
echo "📊 Monitoring stock updates...\n\n";

$clients = [];
$database = new Database();
$db = $database->getConnection();

// Initialize last known stock state
$lastStockState = [];
$query = "SELECT id, quantity_available FROM food_items WHERE is_active = 1";
$stmt = $db->query($query);
$initialItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($initialItems as $item) {
    $lastStockState[$item['id']] = (int)$item['quantity_available'];
}
echo "📦 Loaded initial stock state for " . count($lastStockState) . " items\n\n";

while (true) {
    $read = array_merge([$socket], $clients);
    $write = $except = null;

    // Use select with timeout for non-blocking operation
    if (socket_select($read, $write, $except, 0, 100000) < 1) {
        // Check for stock updates every 100ms
        checkStockUpdates($db, $clients, $lastStockState);
        continue;
    }

    // Handle new connection
    if (in_array($socket, $read)) {
        $newClient = socket_accept($socket);

        if ($newClient === false) {
            echo "⚠️ Failed to accept new client\n";
            continue;
        }

        // Perform WebSocket handshake
        $header = socket_read($newClient, 1024);
        if ($header === false) {
            socket_close($newClient);
            continue;
        }

        performHandshake($header, $newClient);
        $clients[] = $newClient;
        echo "✅ New client connected. Total clients: " . count($clients) . "\n";

        // Send initial stock data to new client
        sendInitialStock($db, $newClient);

        $key = array_search($socket, $read);
        unset($read[$key]);
    }

    // Handle client messages
    foreach ($read as $client) {
        $data = @socket_read($client, 1024, PHP_BINARY_READ);

        if ($data === false || $data === '') {
            // Client disconnected
            $key = array_search($client, $clients);
            if ($key !== false) {
                unset($clients[$key]);
                $clients = array_values($clients); // Reindex array
            }
            socket_close($client);
            echo "❌ Client disconnected. Total clients: " . count($clients) . "\n";
            continue;
        }

        // Decode WebSocket frame
        $decodedData = decodeWebSocketFrame($data);

        if ($decodedData) {
            // Handle ping/pong
            if ($decodedData['opcode'] === 0x9) {
                sendWebSocketFrame($client, $decodedData['payload'], 0xA);
            }
        }
    }

    // Periodically check for stock updates
    checkStockUpdates($db, $clients, $lastStockState);
}

socket_close($socket);

/**
 * Perform WebSocket handshake
 */
function performHandshake($header, $client)
{
    $headers = [];
    $lines = preg_split("/\r\n/", $header);

    foreach ($lines as $line) {
        $line = rtrim($line);
        if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    $key = $headers['Sec-WebSocket-Key'] ?? '';
    $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

    $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";

    socket_write($client, $upgrade);
}

/**
 * Decode WebSocket frame
 */
function decodeWebSocketFrame($data)
{
    if (strlen($data) < 2) return false;

    $byte1 = ord($data[0]);
    $byte2 = ord($data[1]);

    $opcode = $byte1 & 0x0F;
    $masked = ($byte2 & 0x80) !== 0;
    $payloadLen = $byte2 & 0x7F;

    $offset = 2;

    if ($payloadLen === 126) {
        if (strlen($data) < $offset + 2) return false;
        $payloadLen = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;
    } elseif ($payloadLen === 127) {
        if (strlen($data) < $offset + 8) return false;
        $payloadLen = unpack('J', substr($data, $offset, 8))[1];
        $offset += 8;
    }

    if ($masked) {
        if (strlen($data) < $offset + 4) return false;
        $maskingKey = substr($data, $offset, 4);
        $offset += 4;

        $payload = '';
        for ($i = 0; $i < $payloadLen && $offset + $i < strlen($data); $i++) {
            $payload .= $data[$offset + $i] ^ $maskingKey[$i % 4];
        }
    } else {
        $payload = substr($data, $offset, $payloadLen);
    }

    return [
        'opcode' => $opcode,
        'payload' => $payload
    ];
}

/**
 * Send WebSocket frame
 */
function sendWebSocketFrame($client, $payload, $opcode = 0x1)
{
    $payloadLen = strlen($payload);
    $frame = chr(0x80 | $opcode);

    if ($payloadLen < 126) {
        $frame .= chr($payloadLen);
    } elseif ($payloadLen < 65536) {
        $frame .= chr(126) . pack('n', $payloadLen);
    } else {
        $frame .= chr(127) . pack('J', $payloadLen);
    }

    $frame .= $payload;
    $result = @socket_write($client, $frame);

    if ($result === false) {
        return false;
    }

    return true;
}

/**
 * Broadcast message to all clients
 */
function broadcast($clients, $message)
{
    $payload = json_encode($message);
    $disconnected = [];

    foreach ($clients as $key => $client) {
        $result = sendWebSocketFrame($client, $payload);
        if ($result === false) {
            $disconnected[] = $key;
        }
    }

    return $disconnected;
}

/**
 * Send initial stock data to new client
 */
function sendInitialStock($db, $client)
{
    try {
        $query = "SELECT id, name, quantity_available, is_active 
                  FROM food_items 
                  WHERE is_active = 1 
                  ORDER BY name ASC";

        $stmt = $db->query($query);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert quantities to integers
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['quantity_available'] = (int)$item['quantity_available'];
            $item['is_active'] = (int)$item['is_active'];
        }

        $message = [
            'type' => 'initial_stock',
            'data' => $items,
            'timestamp' => time()
        ];

        sendWebSocketFrame($client, json_encode($message));
        echo "📤 Sent initial stock data (" . count($items) . " items) to new client\n";
    } catch (Exception $e) {
        echo "❌ Error sending initial stock: " . $e->getMessage() . "\n";
    }
}

/**
 * Check for stock updates and broadcast changes
 */
function checkStockUpdates($db, &$clients, &$lastStockState)
{
    static $lastCheck = 0;
    $now = microtime(true);

    // Check every 0.3 seconds
    if ($now - $lastCheck < 0.3) {
        return;
    }

    $lastCheck = $now;

    try {
        $query = "SELECT id, name, quantity_available, is_active 
                  FROM food_items 
                  WHERE is_active = 1";

        $stmt = $db->query($query);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updates = [];

        foreach ($items as $item) {
            $itemId = (int)$item['id'];
            $currentQty = (int)$item['quantity_available'];

            // Check if stock quantity has changed
            if (!isset($lastStockState[$itemId]) || $lastStockState[$itemId] !== $currentQty) {

                $oldQty = $lastStockState[$itemId] ?? 'N/A';
                echo "🔄 Stock change detected - Item: {$item['name']} (ID: {$itemId}), Old: {$oldQty}, New: {$currentQty}\n";

                $updates[] = [
                    'id' => $itemId,
                    'name' => $item['name'],
                    'quantity_available' => $currentQty,
                    'is_active' => (int)$item['is_active']
                ];

                // Update the last known state
                $lastStockState[$itemId] = $currentQty;
            }
        }

        // Broadcast updates if any changes detected
        if (!empty($updates) && !empty($clients)) {
            $message = [
                'type' => 'stock_update',
                'data' => $updates,
                'timestamp' => time()
            ];

            echo "📢 Broadcasting " . count($updates) . " stock updates to " . count($clients) . " clients\n";

            $disconnected = broadcast($clients, $message);

            // Remove disconnected clients
            foreach ($disconnected as $key) {
                unset($clients[$key]);
                socket_close($clients[$key]);
            }

            if (!empty($disconnected)) {
                $clients = array_values($clients);
                echo "⚠️ Removed " . count($disconnected) . " disconnected clients\n";
            }

            echo "✅ Broadcast complete\n\n";
        }
    } catch (Exception $e) {
        echo "❌ Error checking stock updates: " . $e->getMessage() . "\n";
    }
}
