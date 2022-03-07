<?php

namespace Drupal\app_sitemap;

use Drupal\app\Controller\BaseController;

class SitemapController extends BaseController
{
    public function render()
    {
        header('Content-type: application/xml');

        $slugs = \Drupal::database()->query("SELECT uuid FROM node
LEFT JOIN node__field_standing ON node.nid = node__field_standing.entity_id
WHERE field_standing_value = 'app-allowed'
AND node.type = 'programs'
")->fetchCol(); ?><?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <?php foreach ($slugs as $slug) { ?><url>
    <url>
      <loc><?php print $_ENV['CLIENT_URL'] ?>/en/program/<?php print $slug ?></loc>
    </url>
    <url>
      <loc><?php print $_ENV['CLIENT_URL'] ?>/fr/program/<?php print $slug ?></loc>
    </url>
  </url><?php } ?>
</urlset><?php
    exit;
    }
}
