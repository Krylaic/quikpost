<?php

namespace App\Models;

class PostModel extends BaseModel {

    // functions to fetch data

    public function getAllPosts($limit, $offset) {
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name
            FROM posts
            INNER JOIN users ON posts.author = users.id
            WHERE users.suspended = 0
            ORDER BY posts.post_date DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // functions to update data

}