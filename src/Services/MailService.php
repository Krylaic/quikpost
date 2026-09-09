<?php
namespace App\Services;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService extends BaseService {
    public function sendEmail($email, $subject, $html) {
        $mail = new PHPMailer(true);

        try {
            // render the email template
            $body = $this->twig->render('emails/base.twig', [
                'subject' => $subject,
                'content' => $html
            ]);

            // configure
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_SERVER'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME']; 
            $mail->Password = $_ENV['SMTP_PASSWORD']; 
            $mail->SMTPSecure = 'tls';
            $mail->Port = $_ENV['SMTP_PORT'];
            
            $mail->setFrom($_ENV['SMTP_FROM_MAIL'], $_ENV['SMTP_FROM_NAME']);
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            
            $mail->Body = $body;
            
            // senbd the email
            $mail->send();

            // insert into database
            $date_sent = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare('INSERT INTO emails (destination, content, date_sent) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $email, $body, $date_sent);
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}