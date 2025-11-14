<?php

/**
 * Simple file-based queue for handling stock operations
 * Place this file in: classes/StockQueue.php
 */

class StockQueue
{
    private $queueDir;
    private $lockFile;
    private $maxRetries = 5;
    private $retryDelay = 100000; // 100ms in microseconds

    public function __construct()
    {
        // Create queue directory if it doesn't exist
        $this->queueDir = __DIR__ . '/../queue/stock_operations';
        $this->lockFile = $this->queueDir . '/.lock';

        if (!file_exists($this->queueDir)) {
            mkdir($this->queueDir, 0755, true);
        }
    }

    /**
     * Add a stock operation to the queue
     * @param int $orderId
     * @param array $items - Array of items with 'id' and 'quantity'
     * @return string Queue ID
     */
    public function enqueue($orderId, $items)
    {
        $queueId = uniqid('queue_', true);
        $queueData = [
            'queue_id' => $queueId,
            'order_id' => $orderId,
            'items' => $items,
            'status' => 'pending',
            'created_at' => time(),
            'attempts' => 0
        ];

        $queueFile = $this->queueDir . '/' . $queueId . '.json';
        file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));

        return $queueId;
    }

    /**
     * Process the queue and reduce stock
     * @param PDO $db Database connection
     * @return bool Success status
     */
    public function processQueue($db)
    {
        // Acquire lock to prevent concurrent processing
        $lockHandle = $this->acquireLock();
        if (!$lockHandle) {
            return false;
        }

        try {
            // Get all pending queue files
            $queueFiles = glob($this->queueDir . '/queue_*.json');

            if (empty($queueFiles)) {
                $this->releaseLock($lockHandle);
                return true;
            }

            // Sort by creation time (oldest first)
            usort($queueFiles, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Process each queue item
            foreach ($queueFiles as $queueFile) {
                $queueData = json_decode(file_get_contents($queueFile), true);

                if ($queueData['status'] !== 'pending') {
                    continue;
                }

                try {
                    $db->beginTransaction();

                    $allStockAvailable = true;
                    $stockChecks = [];

                    // Lock and verify stock for all items
                    foreach ($queueData['items'] as $item) {
                        $stmt = $db->prepare("SELECT id, name, quantity_available 
                                             FROM food_items 
                                             WHERE id = :id 
                                             FOR UPDATE");
                        $stmt->bindValue(':id', $item['id'], PDO::PARAM_INT);
                        $stmt->execute();
                        $currentItem = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$currentItem) {
                            $allStockAvailable = false;
                            $queueData['error'] = "Item ID {$item['id']} not found";
                            break;
                        }

                        if ($currentItem['quantity_available'] < $item['quantity']) {
                            $allStockAvailable = false;
                            $queueData['error'] = "Insufficient stock for {$currentItem['name']}. Available: {$currentItem['quantity_available']}, Requested: {$item['quantity']}";
                            break;
                        }

                        $stockChecks[] = $currentItem;
                    }

                    if ($allStockAvailable) {
                        // Reduce stock for all items
                        foreach ($queueData['items'] as $item) {
                            $stmt = $db->prepare("UPDATE food_items 
                                                 SET quantity_available = quantity_available - :qty,
                                                     updated_at = NOW()
                                                 WHERE id = :id");
                            $stmt->bindValue(':qty', $item['quantity'], PDO::PARAM_INT);
                            $stmt->bindValue(':id', $item['id'], PDO::PARAM_INT);
                            $stmt->execute();
                        }

                        $db->commit();

                        // Mark as completed
                        $queueData['status'] = 'completed';
                        $queueData['completed_at'] = time();
                        file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));

                        // Delete completed queue file after 1 hour
                        if (time() - $queueData['completed_at'] > 3600) {
                            unlink($queueFile);
                        }
                    } else {
                        $db->rollback();

                        // Mark as failed
                        $queueData['status'] = 'failed';
                        $queueData['attempts']++;
                        $queueData['failed_at'] = time();
                        file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));
                    }
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollback();
                    }

                    // Mark as failed with error
                    $queueData['status'] = 'failed';
                    $queueData['error'] = $e->getMessage();
                    $queueData['attempts']++;
                    file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));
                }
            }

            $this->releaseLock($lockHandle);
            return true;
        } catch (Exception $e) {
            $this->releaseLock($lockHandle);
            error_log("Queue processing error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check queue status
     */
    public function getQueueStatus($queueId)
    {
        $queueFile = $this->queueDir . '/' . $queueId . '.json';

        if (!file_exists($queueFile)) {
            return null;
        }

        return json_decode(file_get_contents($queueFile), true);
    }

    /**
     * Acquire lock for queue processing
     */
    private function acquireLock()
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            $lockHandle = fopen($this->lockFile, 'c');

            if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
                return $lockHandle;
            }

            fclose($lockHandle);
            usleep($this->retryDelay);
            $attempts++;
        }

        return false;
    }

    /**
     * Release lock
     */
    private function releaseLock($lockHandle)
    {
        if ($lockHandle) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    /**
     * Clean up old queue files
     */
    public function cleanup($maxAge = 86400)
    {
        $queueFiles = glob($this->queueDir . '/queue_*.json');
        $now = time();

        foreach ($queueFiles as $file) {
            if ($now - filemtime($file) > $maxAge) {
                unlink($file);
            }
        }
    }
}
