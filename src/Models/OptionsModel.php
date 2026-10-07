<?php

namespace App\Models;

class OptionsModel extends BaseModel {
    
    public function getSiteName() {
        $result = $this->db->query("SELECT name FROM options LIMIT 1");
        
        $row = $result->fetch_assoc();
        return $row['name'] ?? '';
    }

    public function getDomain() {
        $result = $this->db->query("SELECT domain FROM options LIMIT 1");

        $row = $result->fetch_assoc();
        return $row['domain'] ?? '';
    }

    public function getSlogan() {
        $result = $this->db->query("SELECT slogan FROM options LIMIT 1");

        $row = $result->fetch_assoc();
        return $row['slogan'] ?? '';
    }

    public function getAbout() {
        $result = $this->db->query("SELECT about FROM options LIMIT 1");

        $row = $result->fetch_assoc();
        return $row['about'] ?? '';
    }

    public function getTos() {
        $result = $this->db->query("SELECT tos FROM options LIMIT 1");

        $row = $result->fetch_assoc();
        return $row['tos'] ?? '';
    }

    public function getPrivacyPolicy() {
        $result = $this->db->query("SELECT privacy_policy FROM options LIMIT 1");

        $row = $result->fetch_assoc();
        return $row['privacy_policy'] ?? '';
    }

    public function getCommunityGuidelines() {
        $result = $this->db->query("SELECT community_guidelines FROM options LIMIT 1");

        $row = $result->fetch_assoc();
        return $row['community_guidelines'] ?? '';
    }

    public function getIpCheck() {
        $result = $this->db->query("SELECT ip_check FROM options LIMIT 1");

        $row = $result->fetch_assoc();
        return $row['ip_check'] ?? '';
    }
}