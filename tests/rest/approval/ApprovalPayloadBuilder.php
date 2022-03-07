<?php

namespace rest\approval;

class ApprovalPayloadBuilder
{
    public $approvalStatus;
    public $entityType;
    public $entityId;

    public function execute()
    {
        return [
            "data" => [
                "type" => "node--approval",
                "attributes" => [
                    "field_status" => $this->approvalStatus,
                    "field_notes" => ""
                ],
                "relationships" => [
                    "field_approval_entity" => [
                        "data" => [
                            [
                                "type" => "node--{$this->entityType}",
                                "id" => $this->entityId
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
