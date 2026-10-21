<?php

namespace App\Models;

class SiteModel extends BaseModel {

    public function contact($content, $email) {

        // current date
        $contact_date = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('INSERT INTO contact (content, email, contact_date) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $content, $email, $contact_date);
        if($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Error submitting report.'];
        }
    }

    
}