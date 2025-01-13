<?php

namespace Solspace\Freeform\Tests\Library\Templates;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\DataObjects\FormTemplate;
use Solspace\Freeform\Library\Templates\TemplateLocator;
use Symfony\Component\Finder\Finder;

#[CoversClass(TemplateLocator::class)]
class TemplateLocatorTest extends TestCase
{
    private TemplateLocator $locator;

    protected function setUp(): void
    {
        $this->locator = new TemplateLocator(new Finder());
    }

    public function testLocate()
    {
        $folder = __DIR__.'/mock-template-folder';
        $templates = $this->locator->locate($folder);

        $this->assertCount(3, $templates);
        $this->assertEquals([
            new FormTemplate($folder.'/template-folder-one/index.twig', $folder),
            new FormTemplate($folder.'/template-one.twig', $folder),
            new FormTemplate($folder.'/template-two.html', $folder),
        ], $templates);
    }
}
