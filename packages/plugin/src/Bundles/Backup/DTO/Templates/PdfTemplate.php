<?php

namespace Solspace\Freeform\Bundles\Backup\DTO\Templates;

class PdfTemplate
{
    public ?string $uid = null;
    public ?int $id = null;

    public string $name;
    public string $fileName;
    public ?string $description = null;
    public string $body;
}
