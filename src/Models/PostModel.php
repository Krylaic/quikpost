<?php

namespace App\Models;
use App\Models\UserModel;

class PostModel extends BaseModel {

    // functions to fetch data

    // any of these big massive fancy queries you see below are made with chatgpt, i can't be bothered to seriously learn how to make crazy queries like these.

    public function getAllPosts($limit, $offset) {
        $current_user_id = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE users.suspended = 0
            ORDER BY posts.post_date DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('iii', $current_user_id, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPostsByQuery($query, $limit, $offset) {
        $current_user_id = (int)($_SESSION['user_id'] ?? 0);
        $search_term = '%' . $query . '%';

        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE users.suspended = 0 AND posts.content LIKE ?
            ORDER BY posts.post_date DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('isii', $current_user_id, $search_term, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getFeedPosts($limit, $offset, $user) {
        $current_user_id = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
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

        $stmt->bind_param('issii', $current_user_id, $user, $user, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getPostById($postId) {
        $current_user_id = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE posts.id = ?
            AND users.suspended = 0
        ');

        $stmt->bind_param('ii', $current_user_id, $postId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function doesPostExist($id) {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?'); 
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 0) {
            return false;
        }
        return true;
    }

    public function getPostsByUserId($user_id, $limit = 15, $offset = 0) {
        $current_user_id = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE posts.author = ?
            AND users.suspended = 0
            ORDER BY posts.id DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('iiii', $current_user_id, $user_id, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPostReplies($post_id, $limit = 15, $offset = 0) {
        $current_user_id = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $this->db->prepare('
            SELECT 
                posts.*, 
                users.username AS author_name, 
                users.avatar AS author_avatar,
                users.display_name AS author_display_name,
                reply_posts.content AS reply_to_content,
                (SELECT COUNT(*) FROM posts AS replies WHERE replies.reply_to = posts.id) AS reply_count,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
            FROM posts
            INNER JOIN users ON posts.author = users.id
            LEFT JOIN posts AS reply_posts ON posts.reply_to = reply_posts.id
            WHERE posts.reply_to = ?
            AND users.suspended = 0
            ORDER BY posts.post_date DESC LIMIT ? OFFSET ?
        ');

        $stmt->bind_param('iiii', $current_user_id, $post_id, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }


    // functions to update data

    public function createPost($content, $author, $attachment, $reply_to) {
        $userModel = new UserModel();

        $post_date = date('Y-m-d H:i:s');

        // rate limiting
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE author = ? AND post_date >= (? - INTERVAL 1 MINUTE)");
        $stmt->bind_param('ss', $author, $post_date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows >= 3) {  
            return ['success' => false, 'error' => 'Please wait a bit before posting again!'];
        }

        if($userModel->fetchVerificationStatus($_SESSION['user_id']) == false) {
            return ['success' => false, 'error' => 'Please verify before posting!'];
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

    public function likePost($id) {
        // make sure post exists
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 0) {
            return ['success' => false, 'error' => 'This post does not exist!'];
        }

        // check if the post has already been liked by the user
        $stmt = $this->db->prepare('SELECT * FROM likes WHERE post_id = ? AND user_id = ? LIMIT 1');
        $stmt->bind_param('ii', $id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows == 0) {
            // if not, add a like to the post
            $stmt = $this->db->prepare('INSERT INTO likes (post_id, user_id) VALUES (?, ?)');
            $stmt->bind_param('ii', $id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            return(["status" => "liked"]);
        } else {
            // otherwise, unlike the post
            $stmt = $this->db->prepare('DELETE FROM likes WHERE post_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            return(["status" => "unliked"]);
        }
    }

    public function deletePost($id) {
        $userModel = new UserModel();

        // make sure post exists
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 0) {
            return ['success' => false, 'error' => 'This post does not exist!'];
        }

        $post = $result->fetch_assoc();

        $admin_level = $userModel->fetchAdminStatus($_SESSION['user_id']);

        if ($admin_level <= 0 && $post['author'] != $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'You do not have permission to delete this post!'];
        }

        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return ['success' => true];
    }

    
}