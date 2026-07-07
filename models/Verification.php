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
            $queryCheck = "SELECT t.file_lampiran
                       FROM tasks t
                       JOIN verifications v ON v.tasks_idtasks = t.id
                       WHERE v.id = ?";

            $stmtCheck = $this->conn->prepare($queryCheck);
            $stmtCheck->bindParam(1, $id, PDO::PARAM_INT);
            $stmtCheck->execute();

            $data = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (empty($data['file_lampiran'])) {
                throw new Exception("Task belum disubmit sehingga belum dapat diverifikasi.");
            }

            $query = "UPDATE " . $this->table_name . "
                  SET status = ?, catatan = ?, tanggal_approval = NOW()
                  WHERE id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $status);
            $stmt->bindParam(2, $catatan);
            $stmt->bindParam(3, $id);

            if (!$stmt->execute()) {
                throw new Exception("Gagal update status");
            }

            $payload = $this->getApprovalPayload($id, $status, $catatan);

            if (!empty($payload)) {
                $this->sendToHyperledger($payload);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {

            $this->conn->rollBack();
            error_log($e->getMessage());

            return false;
        }
    }

    private function getApprovalPayload($verificationId, $status, $catatan)
    {
        $query = "SELECT
                    v.users_id,
                    v.tasks_idtasks,
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
            'id' => "VER" . $verificationId,
            'taskID' => "TASK" . $row['tasks_idtasks'],
            'verifierID' => (string)$_SESSION['user_id'],
            'status' => $status,
            'catatan' => $catatan
        ];
    }

    private function sendToHyperledger($payload)
    {
        $options = [
            "http" => [
                "header" => "Content-Type: application/json",
                "method" => "POST",
                "content" => json_encode($payload)
            ]
        ];

        $response = file_get_contents(
            "http://localhost:3000/api/verification",
            false,
            stream_context_create($options)
        );

        if ($response === false) {
            error_log("Gagal mengirim Verification ke blockchain");
        }
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
