<?php

namespace rest\approval;

use GuzzleHttp\RequestOptions;
use rest\Session;

class ApprovalUtils
{
    public static function getApprovalData($entityId, $approvalStatus = "app-allowed", $entityType = 'programs')
    {
        $payloadBuilder = new ApprovalPayloadBuilder();
        $payloadBuilder->entityId = $entityId;
        $payloadBuilder->approvalStatus = $approvalStatus;
        $payloadBuilder->entityType = $entityType;
        return $payloadBuilder->execute();
    }

    public static function changeApprovalStatus($entityId, $approvalStatus = "app-allowed", $entityType = 'programs', $uilang = 'en')
    {
        $globalAdministratorSession = new Session();
        $globalAdministratorSession->signIn();
        $approvalData = self::getApprovalData($entityId, $approvalStatus, $entityType, $uilang);
        $data = [
            RequestOptions::JSON => $approvalData
        ];
        return $globalAdministratorSession->request('POST', "a/app/approval", $data);
    }

    public static function getValidStatusVals()
    {
        return new ApprovalTypes();
    }
}
