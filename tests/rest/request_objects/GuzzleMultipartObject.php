<?php

namespace rest\request_objects;

use GuzzleHttp\Psr7;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GuzzleMultipartObject
{
    public string $name;
    public $contents;
    public $contentsAreEncoded = false;

    public function __construct($name = "entityData")
    {
        $this->contents = new RequestContents();
        $this->name = $name;
    }

    public function encodeContents()
    {
        if (!$this->contentsAreEncoded) {
            $this->contents = json_encode($this->contents);
        }
        $this->contentsAreEncoded = true;
    }

    public function transformToDataArray(): array
    {
        $data = [];
        $this->encodeContents();
        $data[0] = (array)$this;
        unset($data[0]['contentsAreEncoded']);
        return $data;
    }

    public function transformToDataArrayIncludingPhoto($directory = '/../../logo.png', $fileName = 'logo.png', $mimeType = 'image/png'): array
    {
        $data = $this->transformToDataArray();
        $photo = new UploadedFile(__DIR__ . $directory, $fileName, $mimeType);
        $photoData = new GuzzleMultipartObject("files[logo]");
        $photoData->contents = Psr7\Utils::tryFopen($photo, 'r');
        $data[1] = (array)$photoData;
        unset($data[1]['contentsAreEncoded']);
        return $data;
    }
}
