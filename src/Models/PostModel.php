<?php

namespace App\Models;

class PostModel extends BaseModel {

    // functions to fetch data

    public function getAllPosts($limit, $offset) {
        // i didnt make this query, chatgpt did
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE users.suspended = 0
            ORDER BY posts.post_date DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getFeedPosts($limit, $offset, $user) {
        // i also didnt make this query chatgpt made it
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE users.suspended = 0
            AND (
                posts.author = ?
                OR posts.author IN (
                    SELECT following
                    FROM followers
                    WHERE follower = ?
                )
            )
            ORDER BY posts.post_date DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('ssii', $user, $user, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getPostById($postId) {
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE posts.id = ?
            AND users.suspended = 0
        ');

        $stmt->bind_param('i', $postId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function getPostsByUserId($user_id, $limit = 15, $offset = 0) {
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE posts.author = ?
            AND users.suspended = 0
            ORDER BY posts.id DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('iii', $user_id, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPostReplies($post_id, $limit = 15, $offset = 0) {
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE posts.reply_to = ?
            AND users.suspended = 0
            ORDER BY posts.post_date DESC LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('iii', $post_id, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }


    // functions to update data

    public function createPost($content, $author, $attachment, $reply_to) {
        $post_date = date('Y-m-d H:i:s');

        // rate limiting
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE author = ? AND post_date >= (? - INTERVAL 1 MINUTE)");
        $stmt->bind_param('ss', $author, $post_date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows >= 3) {  
            return ['success' => false, 'error' => 'Please wait a bit before posting again!'];
        }

        // make sure the post the user is replying to actually exists
        if($reply_to !== null) {
            $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->bind_param('s', $reply_to);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {  
                return ['success' => false, 'error' => 'You are attempting to reply to a post that doesn\'t exist!'];
            }
        }
        
        // make the post
        $stmt = $this->db->prepare("INSERT INTO posts (content, author, post_date, attachment, reply_to) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $content, $author, $post_date, $attachment, $reply_to);
        
        if($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'There was a problem making your post'];
        }
    }

    public function createReport($id, $content, $captcha) {
        // make sure the post exists
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 0) {
            return ['success' => false, 'error' => 'This post does not exist!'];
        }

        // limit to one report per user
        $stmt = $this->db->prepare('SELECT * FROM reports WHERE post_id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            return ['success' => false, 'error' => 'You already reported this post!'];
        }

        // current date
        $report_date = date('Y-m-d H:i:s');

        // put the report in the db
        $stmt = $this->db->prepare('INSERT INTO reports (content, post_id, user_id, report_date) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('siis', $content, $id, $_SESSION['user_id'], $report_date);
        if($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Error submitting report.'];
        }
    }
}