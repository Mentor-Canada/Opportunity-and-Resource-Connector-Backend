<?php

namespace Drupal\app\Collection;

use DateTime;

class JSONApiDocumentBuilder
{
    private $document;

    public function __construct()
    {
        $uri = explode("?", $_SERVER[REQUEST_URI]);
        $qs = $uri[1];
        parse_str($qs, $params);
        $qs = http_build_query($qs);
        $this->document = [
            'links' => [
                'self' => ["href" => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]{$uri[0]}$qs"]
            ]
        ];
    }

    public function total($total): JSONApiDocumentBuilder
    {
        $this->document['total'] = $total;
        return $this;
    }

    public function data($data): JSONApiDocumentBuilder
    {
        $data = array_map(function ($item) {
            if (is_string($item->created)) {
                $dateTime = new DateTime();
                $dateTime->setTimestamp($item->created);
                $item->created = $dateTime->format('Y-m-d\TH:i:sP');
            }
            return [
                "id" => !empty($item->id) ? $item->id : null,
                "attributes" => $item
            ];
        }, $data);
        $this->document['data'] = $data;
        return $this;
    }

    public function next($offset)
    {
        $this->document['links']['next'] = ['href' => $this->offsetUrl($offset)];
    }

    public function prev($offset)
    {
        if ($offset < 0) {
            $offset = 0;
        }
        $this->document['links']['prev'] = ['href' => $this->offsetUrl($offset)];
    }

    private function offsetUrl($offset)
    {
        $components = explode("?", $_SERVER['REQUEST_URI']);
        parse_str($components[1], $params);
        $params['page']['offset'] = $offset;
        $queryString = http_build_query($params);
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$components[0]?$queryString";
    }

    public function execute(): array
    {
        return $this->document;
    }
}
