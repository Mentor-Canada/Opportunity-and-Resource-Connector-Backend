<?php

namespace Drupal\app_inquiry;

class DateRangeAdapter
{
    public $start;
    public $end;

    public function __construct($mode)
    {
        if ($mode == 'filter-this-week') {
            $this->thisMonth();
        }
    }

    private function thisMonth()
    {
        $day = date('w');
        $this->start = strtotime(date('Y-m-d', strtotime('-'.($day - 1).' days')));
        $this->end = strtotime(date('Y-m-d', strtotime('+'.(7-$day).' days')));
    }
}
