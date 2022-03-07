<?php

namespace Drupal\app_feedback;

use Drupal\app\App;
use Drupal\app\Controller\BaseController;
use Drupal\app\Mailer;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

class FeedbackController extends BaseController
{
    public function submit()
    {
        $content = \Drupal::request()->getContent();
        $content = json_decode($content);

        $node = Node::create([
            type => "feedback",
            FeedbackFields::email => $content->email,
            FeedbackFields::text => $content->message,
            FeedbackFields::url => $content->location
        ]);

        $node->save();

        $lang = App::getInstance()->uilang;

        (new Mailer())
      ->lang($lang)
      ->email($content->email)
      ->subject($lang == "fr" ? "Vos commentaires" : "Your comments")
      ->body($content->message)
      ->mail()
    ;

        $support = "support@mentoringcanada.ca";
        (new Mailer())
      ->lang($lang)
      ->email($support)
      ->replyTo($content->email)
      ->subject($lang == "fr" ? "Commentaires - {$content->email}" : "Comments - {$content->email}")
      ->body($content->message)
      ->mail()
    ;

        return new JsonResponse(['data' => [
            'status' => 'success'
        ]]);
    }
}
