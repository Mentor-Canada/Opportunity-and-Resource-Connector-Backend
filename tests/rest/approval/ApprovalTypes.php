<?php

namespace rest\approval;

class ApprovalTypes
{
    public string $allowed = 'app-allowed';
    public string $pending = 'app-pending';
    public string $suspended = 'app-suspended';
    public string $denied = 'app-denied';
}
