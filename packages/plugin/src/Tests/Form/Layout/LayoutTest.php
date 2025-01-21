<?php

namespace Solspace\Freeform\Tests\Form\Layout;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Bundles\Translations\TranslationProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Form\Layout\FormLayout;
use Solspace\Freeform\Form\Layout\Layout;
use Solspace\Freeform\Form\Layout\Page;

#[CoversClass(Layout::class)]
class LayoutTest extends TestCase
{
    private PropertyProvider $propertyProvider;
    private TranslationProvider $translationProvider;
    private Form $formMock;

    protected function setUp(): void
    {
        $this->propertyProvider = $this->createMock(PropertyProvider::class);
        $this->translationProvider = $this->createMock(TranslationProvider::class);
        $this->formMock = $this->createMock(Form::class);

        $this
            ->translationProvider
            ->method('getTranslation')
            ->willReturnArgument(3)
        ;
    }

    public function testIteratePages()
    {
        $layout = new FormLayout();
        $layout->getPages()
            ->add(new Page($this->formMock, $this->propertyProvider, $this->translationProvider, new Layout(), ['uid' => 'one', 'label' => 'Page One']))
            ->add(new Page($this->formMock, $this->propertyProvider, $this->translationProvider, new Layout(), ['uid' => 'one', 'label' => 'Page Two']))
        ;

        $labels = [];
        foreach ($layout as $page) {
            $labels[] = $page->getLabel();
        }

        $this->assertSame(['Page One', 'Page Two'], $labels);
    }

    public function testCountPages()
    {
        $layout = new FormLayout();
        $layout->getPages()
            ->add(new Page($this->formMock, $this->propertyProvider, $this->translationProvider, new Layout(), ['uid' => 'one', 'label' => 'Page One']))
            ->add(new Page($this->formMock, $this->propertyProvider, $this->translationProvider, new Layout(), ['uid' => 'one', 'label' => 'Page Two']))
        ;

        $this->assertCount(2, $layout);
    }

    public function testGetByIndex()
    {
        $layout = new FormLayout();
        $layout->getPages()
            ->add(new Page($this->formMock, $this->propertyProvider, $this->translationProvider, new Layout(), ['uid' => 'one', 'label' => 'Page One']))
            ->add(new Page($this->formMock, $this->propertyProvider, $this->translationProvider, new Layout(), ['uid' => 'one', 'label' => 'Page Two']))
        ;

        $this->assertSame('Page One', $layout->getPages()->getByIndex(0)->getLabel());
        $this->assertSame('Page Two', $layout->getPages()->getByIndex(1)->getLabel());
    }

    public function testButtonDefaults()
    {
        $layout = new FormLayout();
        $layout->getPages()
            ->add(new Page($this->formMock, $this->propertyProvider, $this->translationProvider, new Layout(), ['uid' => 'one', 'label' => 'Page One']))
            ->add(new Page($this->formMock, $this->propertyProvider, $this->translationProvider, new Layout(), ['uid' => 'one', 'label' => 'Page Two']))
        ;

        $buttons = $layout->getPages()->getByIndex(0)->getButtons();

        $this->assertSame('Submit', $buttons->getSubmitLabel());
        $this->assertSame('Back', $buttons->getBackLabel());
        $this->assertSame('Save', $buttons->getSaveLabel());

        $this->assertFalse($buttons->isBack());
        $this->assertFalse($buttons->isSave());

        $this->assertEmpty($buttons->getSaveRedirectUrl());
        $this->assertNull($buttons->getEmailField());
        $this->assertNull($buttons->getNotificationTemplate());
    }
}
