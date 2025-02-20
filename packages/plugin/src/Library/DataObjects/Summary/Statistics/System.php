<?php

namespace Solspace\Freeform\Library\DataObjects\Summary\Statistics;

class System
{
    public string $databaseDriver = '';
    public string $phpVersion = '';
    public string $craftVersion = '';
    public string $craftEdition = '';
    public bool $formFieldType = false;
    public bool $submissionsFieldType = false;
    public bool $userGroups = false;
    public bool $multiSite = false;
    public bool $languages = false;
    public array $plugins = [];
}
