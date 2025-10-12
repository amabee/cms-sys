<?php
class SystemLogger
{
    private $logFile;
    private $db;

    public function __construct()
    {
        $this->logFile = __DIR__ . '/../logs/system.log';
        // ensure logs directory exists
        $logsDir = dirname($this->logFile);
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0755, true);
        }

        // try to get DB connection but don't fail if unavailable
        if (function_exists('getDBConnection')) {
            $this->db = @getDBConnection();
        } else {
            $this->db = null;
        }
    }

    public function log($userId, $action, $message = null)
    {
        $time = date('Y-m-d H:i:s');
        $entry = "[$time] action=$action user_id=" . ($userId ?? 'NULL') . " message=" . ($message ?? '') . PHP_EOL;
        @file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);

        // attempt to write to audit_log table if DB available
        if ($this->db) {
            try {
                $stmt = $this->db->prepare('INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, created_at) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $action, null, null, json_encode(['message' => $message]), $time]);
            } catch (Exception $e) {
                // ignore db errors
            }
        }
    }

    public function logAuthAction($userId, $action)
    {
        $this->log($userId, $action, 'auth_event');
    }
}

?>
