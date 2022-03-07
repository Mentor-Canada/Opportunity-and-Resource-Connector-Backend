<?php

namespace Drupal\app\Commands;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class EmailBuilder
{
    private string $fromAddress;
    private string $fromName;

    private string $toEmail;
    private string $toFirstName;
    private string $toLastName;

    private string $subject;
    private string $html;
    private string $text;
    private array $attachments = [];
    private array $files = [];

    public function from($address, $name): EmailBuilder
    {
        $this->fromAddress = $address;
        $this->fromName = $name;
        return $this;
    }

    public function to($email, $firstName, $lastName)
    {
        $this->toEmail = $email;
        $this->toFirstName = $firstName;
        $this->toLastName = $lastName;
        return $this;
    }

    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function html($html)
    {
        $this->html = $html;
        return $this;
    }

    public function text($text)
    {
        $this->text = $text;
        return $this;
    }

    public function attachments($attachments)
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function files($files)
    {
        $this->files = $files;
        return $this;
    }

    public function send()
    {
        $mail = new PHPMailer(true);

//    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host = $_ENV['EMAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['EMAIL_USERNAME'];
        $mail->Password = $_ENV['EMAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($_ENV['EMAIL_FROM'], 'Mentoring Connector');
        $mail->addReplyTo($this->fromAddress, $this->fromName);

        $mail->addAddress($this->toEmail, "{$this->toFirstName} {$this->toLastName}");

        $mail->isHTML(true);
        $mail->AllowEmpty = true;
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $this->subject;
        $mail->Body = $this->html;
        $mail->AltBody = $this->text;
        foreach ($this->attachments as $attachment) {
            $mail->addStringAttachment($attachment->getContent(), $attachment->getFilename());
        }
        foreach ($this->files as $file) {
            $mail->addAttachment($file['tmp_name'], $file['name']);
        }

        $mail->send();
    }
}
