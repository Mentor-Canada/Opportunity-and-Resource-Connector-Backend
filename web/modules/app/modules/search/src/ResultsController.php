<?php

namespace Drupal\app_search;

use Drupal\app\Controller\BaseController;
use Drupal\app\Utils\GooglePlaceUtils;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResultsController extends BaseController
{
    /**
     * @deprecated
     */
    public function getWithUuid($uuid)
    {
        $select = \Drupal::database()->select("searches");
        $select->addField('searches', 'age');
        $select->addField('searches', 'grade');
        $select->addField('searches', 'focus');
        $select->addField('searches', 'youth');
        $select->addField('searches', 'typeOfMentoring');
        $select->addField('searches', 'distance');
        $select->addField("searches", "location");
        $select->addField("searches", "siteBasedDelivery");
        $select->addField("searches", "communityBasedDelivery");
        $select->addField("searches", "eMentoringDelivery");
        $select->addExpression("JSON_EXTRACT(location, '$.geometry.location.lat')", "lat");
        $select->addExpression("JSON_EXTRACT(location, '$.geometry.location.lng')", "lng");
        $select->condition("uuid", $uuid);
        $row = $select->execute()->fetchAll();
        $row = current($row);

        $adapter = new ResultSearchAdapter($row, $_REQUEST);

        return self::getResults($adapter);
    }

    public function getWithLocation($location)
    {
        $adapter = new SearchParamsAdapter($location, $_REQUEST);
        return self::getResults($adapter);
    }

    private function getResults(SearchParamsInterface $adapter)
    {
        $builder = (new ResultCollectionBuilder($adapter))
            ->age($adapter->age())
            ->grade($adapter->grade())
            ->focus($adapter->focus())
            ->youth($adapter->youth())
            ->type($adapter->type())
        ;

        $ids = [];
        if ($adapter->communityBased()) {
            $builder->communityBased();
            $communityBasedIds = (new CommunityBasedProgramCollection($adapter))->ids();
            $ids = array_merge($ids, $communityBasedIds);
        }
        if ($adapter->siteBased()) {
            $builder->siteBased();
            $siteBasedIds = (new SiteBasedProgramCollection($adapter))->ids();
            $ids = array_merge($ids, $siteBasedIds);
        }
        if ($adapter->siteBased() && $adapter->communityBased()) {
            $builder->physical();
        }
        if ($adapter->eMentoring()) {
            $builder->eMentoring();
            $eMentoringIds = (new ContainedProgramCollection($adapter->location(), "eMentoring"))->ids();
            $ids = array_merge($ids, $eMentoringIds);
        }

        $ids = array_unique($ids);

        // sort
        if ($adapter->communityBased() && !$adapter->siteBased() && !$adapter->eMentoring()) {
            $algorithm = new CommunitySortAlgorithm();
        } elseif ($adapter->siteBased() && !$adapter->communityBased() && !$adapter->eMentoring()) {
            $algorithm = new SiteBasedSortAlgorithm();
        } elseif ($adapter->communityBased() && $adapter->siteBased() && !$adapter->eMentoring()) {
            $algorithm = new CommunityAndSiteBasedSortAlgorithm();
        } else {
            $algorithm = new DefaultSortAlgorithm();
        }

        // priority
        $priorityIds = [];

        if ($adapter->siteBased() && $adapter->eMentoring() && !$adapter->communityBased()) {
            $priorityBuilder = $this->getPriorityBuilder($adapter)
                ->sortAlgorithm(new SiteBasedSortAlgorithm())
                ->siteBased()
                ->ids($siteBasedIds)
                ->range(3)
      ;
            $priorityRows = $priorityBuilder->exec();
            $priorityIds = array_column($priorityRows, "nid");
        } elseif ($adapter->communityBased() && $adapter->eMentoring() && !$adapter->siteBased()) {
            $priorityBuilder = $this->getPriorityBuilder($adapter)
                ->sortAlgorithm(new CommunitySortAlgorithm())
                ->communityBased()
                ->ids($communityBasedIds)
                ->range(3)
            ;
            $priorityRows = $priorityBuilder->exec();
            $priorityIds = array_column($priorityRows, "nid");
        } elseif ($adapter->communityBased() && $adapter->siteBased() && $adapter->eMentoring()) {
            $priorityBuilder = $this->getPriorityBuilder($adapter)
                ->sortAlgorithm(new CommunityAndSiteBasedSortAlgorithm())
                ->siteBased()
                ->communityBased()
                ->physical()
                ->ids(array_merge($siteBasedIds, $communityBasedIds))
                ->range(5)
            ;
            $priorityRows = $priorityBuilder->exec();
            $priorityIds = array_column($priorityRows, "nid");
        }

        $builder
            ->sortAlgorithm($algorithm)
            ->ids($ids)
            ->priority($priorityIds)
        ;

        $total = $builder->execCount();
        if ($adapter->limit()) {
            $builder->range($adapter->limit(), $adapter->offset());
        }

        $rows = $builder->exec();

        $response = [
            'data' => $rows
        ];
        if ($adapter->limit() != null) {
            $response['meta'] = [
                'pagination' => [
                    'position' => intval($adapter->offset()),
                    'total' => $total,
                    'totalPages' => ceil($total / $adapter->limit()),
                    'rangeStart' => min($adapter->offset() * $adapter->limit() + 1, $total),
                    'rangeEnd' => min($adapter->offset() + $adapter->limit(), $total)
                ]
            ];
        }

        return new JsonResponse($response);
    }

    public function getPriorityBuilder($adapter): ResultCollectionBuilder
    {
        return (new ResultCollectionBuilder($adapter))
            ->age($adapter->age())
            ->grade($adapter->grade())
            ->focus($adapter->focus())
            ->youth($adapter->youth())
            ->type($adapter->type());
    }
}
