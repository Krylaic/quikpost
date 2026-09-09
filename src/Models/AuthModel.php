<?php

namespace App\Models;
use SimpleCaptcha\Builder;

class AuthModel extends BaseModel {
    
    public function genCaptcha() {
        $builder = new Builder;


        $builder->applyEffects = false;

        $builder->build();

        $_SESSION['captcha'] = $builder->phrase;

        return $builder->output();
    }

    public function verifyCaptcha($form_input) {
        if (empty($_SESSION['captcha'])) {
            return false;
        }
        $builder = new Builder($_SESSION['captcha']);
        return $builder->compare($form_input);
    }

    public function usernameExists($username) {
        // check if the username has been taken on the site
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function emailExists($email) {
        // check if the email has already been used before on the site
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function authenticatePassword($username, $password) {
        // get the hashed password from the database
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // make sure the user exists, if so, log them in
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            return true;
        } else {
            return false;
        }

        
    }

    public function registerUser($username, $email, $password) {
        // limit registration based on ip address
        $stmt = $this->db->prepare('SELECT * FROM ip_addresses WHERE address = ? LIMIT 1');
        $stmt->execute([$_SERVER['REMOTE_ADDR']]);

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return ['success' => false, 'error' => 'An account has already been registered from this IP address!'];
        }

        // insert the new user into the database
        $join_date = date('Y-m-d H:i:s');

        $verify_token = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(20/strlen($x)) )),1,20);

        $stmt = $this->db->prepare('INSERT INTO users (username, email, password, join_date, verify_token) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $username, $email, $password, $join_date, $verify_token);
        if (!$stmt->execute()) {
            return ['success' => false, 'error' => 'There was an error creating your account!'];
        }

        // apparently you dont need to do another query to get the id of whatever you just inserted... this is new to me
        $_SESSION['user_id'] = $this->db->insert_id;

        return ['success' => true, 'token' => $verify_token];
    }

}