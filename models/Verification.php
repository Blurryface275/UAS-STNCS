<?php

class Verification
{
    private $conn;
    private $table_name = "verifications";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read()
    {
        $query = "SELECT 
                    v.*, 
                    u.nama AS user_name,
                    t.aktivitas,
                    t.deskripsi,
                    t.tanggal,
                    t.deadline,
                    t.file_lampiran,
                    t.file_hash, 
                    t.latitude,
                    t.longitude,
                    t.submitted_at
                  FROM " . $this->table_name . " v
                  LEFT JOIN users u ON v.users_id = u.id
                  LEFT JOIN tasks t ON v.tasks_idtasks = t.id
                  ORDER BY v.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readPaging($from_record_num, $records_per_page, $powerLevel = null)
    {
        $query = "SELECT 
                    v.*, 
                    u.nama AS user_name,
                    t.aktivitas,
                    t.deskripsi,
                    t.tanggal,
                    t.deadline,
                    t.file_lampiran,
                    t.file_hash, 
                    t.latitude,
                    t.longitude,
                    t.submitted_at
                  FROM " . $this->table_name . " v
                  LEFT JOIN users u ON v.users_id = u.id
                  LEFT JOIN tasks t ON v.tasks_idtasks = t.id";

        if ($powerLevel !== null && $powerLevel < 5) {
            $query .= " WHERE (6 - u.tipe_users_id) < :powerLevel";
        }

        $query .= " ORDER BY v.id DESC LIMIT :from, :limit";

        $stmt = $this->conn->prepare($query);
        
        if ($powerLevel !== null && $powerLevel < 5) {
            $stmt->bindParam(':powerLevel', $powerLevel, PDO::PARAM_INT);
        }
        $stmt->bindParam(':from', $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function count($powerLevel = null, $status = null)
    {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name . " v LEFT JOIN users u ON v.users_id = u.id";
        
        $conditions = [];
        if ($powerLevel !== null && $powerLevel < 5) {
            $conditions[] = "(6 - u.tipe_users_id) < :powerLevel";
        }
        if ($status !== null) {
            $conditions[] = "v.status = :status";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($powerLevel !== null && $powerLevel < 5) {
            $stmt->bindParam(':powerLevel', $powerLevel, PDO::PARAM_INT);
        }
        if ($status !== null) {
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_row'];
    }

    public function updateStatus($id, $status, $catatan)
    {
        $this->conn->beginTransaction();
        try {   
             $query = "UPDATE " . $this->table_name . "
                  SET status = ?, catatan = ?, tanggal_approval = NOW()
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $catatan);
        $stmt->bindParam(3, $id);

        return $stmt->execute();

        if ($status === 'Disetujui') {
                $payload = $this->getApprovalPayload($id);
                if (!empty($payload)) {
                    $this->sendToHyperledger($payload);
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Verification update failed: ' . $e->getMessage());
            return false;
        }
       
    }

        private function getApprovalPayload($verificationId)
    {
        $query = "SELECT 
                    v.users_id,
                    u.nama AS user_name,
                    t.aktivitas,
                    t.file_hash,
                    t.latitude,
                    t.longitude,
                    t.submitted_at
                  FROM " . $this->table_name . " v
                  LEFT JOIN users u ON v.users_id = u.id
                  LEFT JOIN tasks t ON v.tasks_idtasks = t.id
                  WHERE v.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $verificationId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'recipient_id' => $row['users_id'] ?? null,
            'employee_name' => $row['user_name'] ?? '',
            'task_name' => $row['aktivitas'] ?? '',
            'file_hash' => $row['file_hash'] ?? '',
            'latitude' => $row['latitude'] ?? '',
            'longitude' => $row['longitude'] ?? '',
            'submitted_at' => $row['submitted_at'] ?? '',
            'verification_id' => (int) $verificationId,
            'status' => 'Disetujui'
        ];
    }

    private function sendToHyperledger($payload)
    {
        $endpoint = getenv('HYPERLEDGER_API_URL') ?: getenv('FABRIC_API_URL') ?: ($_ENV['HYPERLEDGER_API_URL'] ?? null);
        if (empty($endpoint)) {
            error_log('Hyperledger endpoint not configured. Skipping blockchain sync.');
            return true;
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $token = getenv('HYPERLEDGER_API_TOKEN') ?: getenv('FABRIC_API_TOKEN') ?: ($_ENV['HYPERLEDGER_API_TOKEN'] ?? null);
        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $payloadJson = json_encode($payload);
        if ($payloadJson === false) {
            error_log('Failed to encode Hyperledger payload.');
            return false;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode >= 400) {
                error_log('Hyperledger sync failed: ' . ($error ?: 'HTTP ' . $httpCode));
                return false;
            }

            return true;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode($headers, "\r\n"),
                'content' => $payloadJson,
                'timeout' => 10
            ]
        ]);

        $response = @file_get_contents($endpoint, false, $context);
        if ($response === false) {
            error_log('Hyperledger sync failed via file_get_contents.');
            return false;
        }

        return true;
    }

    public function create($tasks_id, $users_id)
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (tasks_idtasks, users_id, status, catatan)
                  VALUES (?, ?, 'Pending', 'Menunggu verifikasi')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $tasks_id);
        $stmt->bindParam(2, $users_id);

        return $stmt->execute();
    }
}
?>