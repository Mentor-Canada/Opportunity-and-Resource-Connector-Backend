<?php

namespace Drupal\app\Utils;

use Drupal\app\Commands\EmailBuilder;
use PhpMimeMailParser\Parser;

class EmailUtils
{
    public static function relay($raw)
    {
        $parser = new Parser();
        $parser->setText($raw);

        $to = $parser->getHeader('to');
        $id = explode("@", $to)[0];
        $id = preg_replace("/[^A-Za-z0-9\- ]/", '', $id);
        $applicant = Utils::getApplicantInfo($id);

        $subject = $parser->getHeader('subject');
        $text = $parser->getMessageBody('text');
        $html = $parser->getMessageBody('html');
        $fromName = $parser->getHeader('from');
        $attachments = $parser->getAttachments();

        $matches = [];
        preg_match("/From\s([^\s]*)\s/", $raw, $matches);
        $fromAddress = $matches[1];

        (new EmailBuilder())
      ->from($fromAddress, $fromName)
      ->to($applicant['email'], $applicant['firstName'], $applicant['lastName'])
      ->subject($subject)
      ->html($html)
      ->text($text)
      ->attachments($attachments)
      ->send();

        /** @var \Drupal\node\Entity\Node $searchNode */
        $searchNode = \Drupal::service('entity.repository')->loadEntityByUuid('node', $id);
        $searchNode->set('field_status', 'app-contacted');
        $searchNode->save();
    }
}
