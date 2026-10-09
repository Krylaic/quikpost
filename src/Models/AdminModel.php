<?php

namespace App\Models;

class AdminModel extends BaseModel {


    public function fetchAuditLog($limit, $offset) {
        $stmt = $this->db->prepare('SELECT * FROM audit ORDER BY date DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

}
