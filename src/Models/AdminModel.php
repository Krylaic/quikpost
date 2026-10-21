<?php

namespace App\Models;

class AdminModel extends BaseModel {


    public function fetchAuditLog($limit, $offset) {
	    $stmt = $this->db->prepare('
	        SELECT audit.*, users.username AS admin 
	        FROM audit 
	        LEFT JOIN users ON audit.admin = users.id 
	        ORDER BY audit.date DESC 
	        LIMIT ? OFFSET ?
	    ');
	    $stmt->bind_param('ii', $limit, $offset);
	    $stmt->execute();
	    
	    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	}

    public function updateSite($site_name, $slogan, $domain, $ip_check, $about, $tos, $privacy_policy, $community_guidelines) {
        $current_user = $_SESSION['user_id'];

        $stmt = $this->db->prepare("UPDATE options SET name = ?, slogan = ?, domain = ?, about = ?, tos = ?, privacy_policy = ?, community_guidelines = ?, ip_check = ?");
        $stmt->bind_param('sssssssi', $site_name, $slogan, $domain, $about, $tos, $privacy_policy, $community_guidelines, $ip_check);
        
        if($stmt->execute()) {
            $stmt = $this->db->prepare("INSERT INTO audit (action, admin, date) VALUES (?, ?, ?)");
            $action = "Updated site configuration";
            $date = date('Y-m-d H:i:s');

            $stmt->bind_param('sss', $action, $current_user, $date);

            if($stmt->execute()){
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

}
