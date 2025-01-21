<?php

namespace Solspace\Freeform\Library\DataObjects\Summary\Statistics;

class Spam
{
    public ?string $spamProtectionBehavior = '';
    public ?bool $spamFolder = false;
    public ?bool $purgeSpam = false;
    public ?int $purgeInterval = 0;
    public ?bool $blockEmail = false;
    public ?bool $blockKeywords = false;
    public ?bool $blockIp = false;
    public ?bool $submissionThrottling = false;
    public ?bool $minSubmitTime = false;
    public ?bool $submitExpiration = false;
    public ?bool $bypassSpamCheckOnLoggedInUsers = false;
    public ?int $submissionThrottlingCount = 0;
}
