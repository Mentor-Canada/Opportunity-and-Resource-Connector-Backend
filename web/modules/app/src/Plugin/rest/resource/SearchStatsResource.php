<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\app\Search\SearchCollectionBuilder;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "search_stats_resource",
 *   label = @Translation("Search Stats Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/stats/search"
 *   }
 * )
 */
class SearchStatsResource extends ResourceBase
{
    public function get()
    {
        $adapter = new \Drupal\app\Search\SearchRequestAdapter(\Drupal::request());

        $count = (new SearchCollectionBuilder())
      ->partner($adapter->partnerNid)
      ->notify($adapter->notify)
      ->createdStart($adapter->createdStartDate)
      ->createdStop($adapter->createdEndDate)
      ->executeCount();

        $response = ['data' => [
            'searches' => $count
        ]];
        return new ResourceResponse($response);
    }
}
