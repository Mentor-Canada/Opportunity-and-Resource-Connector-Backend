<?php

namespace Drupal\app;

use Drupal;

class Mailer
{
    private $manager;
    private $_template;
    private $_vm;
    private $_email;
    private $_replyTo;
    private $_lang;
    private $_subject;
    private $_file;
    private $_body;

    public function __construct()
    {
        $this->manager = Drupal::service('plugin.manager.mail');
    }

    public function template($template): Mailer
    {
        $this->_template = $template;
        return $this;
    }

    public function body($body): Mailer
    {
        $body = strval($body);
        $body = "<p>".implode("</p><p>", explode("\n", $body))."</p>";
        $this->_body = $body;
        return $this;
    }

    public function viewModel($vm): Mailer
    {
        $this->_vm = $vm;
        return $this;
    }

    public function email($email): Mailer
    {
        $this->_email = $email;
        return $this;
    }

    public function lang($lang): Mailer
    {
        $this->_lang = $lang;
        return $this;
    }

    public function replyTo($email): Mailer
    {
        $this->_replyTo = $email;
        return $this;
    }

    public function subject($subject): Mailer
    {
        $this->_subject = $subject;
        return $this;
    }

    public function addFile($path, $name, $mime): Mailer
    {
        $this->_file = [
            'filepath' => $path,
            'filename' => $name,
            'filemime' => $mime
        ];
        return $this;
    }

    public function mail()
    {
        $data = ['subject' => $this->_subject];
        if ($this->_replyTo) {
            $data['reply-to'] = $this->_replyTo;
        }
        if ($this->_template) {
            $data['body'] = [
                '#theme' => $this->_template,
                '#v' => $this->_vm
            ];
        } elseif ($this->_body) {
            $data['body'] = [strval($this->_body)];
        }
        if ($this->_file) {
            $data['attachments']['file'] = $this->_file;
        }
        $this->manager->mail("app", null, $this->_email, $this->_lang, $data);
    }
}
