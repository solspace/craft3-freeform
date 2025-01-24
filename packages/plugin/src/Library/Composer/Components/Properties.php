<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Library\Composer\Components;

use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Properties\AdminNotificationProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\ConnectionProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\FieldProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\FormProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\IntegrationProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\PageProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\PaymentProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\ValidationProperties;
use Solspace\Freeform\Library\Exceptions\Composer\ComposerException;
use Solspace\Freeform\Library\Rules\RuleProperties;
use Solspace\Freeform\Library\Translations\TranslatorInterface;

class Properties implements \JsonSerializable
{
    public const PAGE_PREFIX = 'page';
    public const FORM_HASH = 'form';
    public const VALIDATION_HASH = 'validation';
    public const INTEGRATION_HASH = 'integration';
    public const CONNECTIONS_HASH = 'connections';
    public const RULES_HASH = 'rules';
    public const ADMIN_NOTIFICATIONS_HASH = 'admin_notifications';
    public const PAYMENT_HASH = 'payment';

    /** @var array */
    private $propertyList;

    /** @var array */
    private $builtProperties;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * Properties constructor.
     *
     * @throws ComposerException
     */
    public function __construct(array $properties, TranslatorInterface $translator)
    {
        $this->translator = $translator;

        foreach ($properties as $key => $value) {
            if (!\is_array($value)) {
                throw new ComposerException(
                    $translator->translate("Properties for key '{key}' is not an array", ['key' => $key])
                );
            }

            if (!isset($value['type'])) {
                throw new ComposerException(
                    $translator->translate("Properties for key '{key}' do not contain TYPE", ['key' => $key])
                );
            }
        }

        $this->propertyList = $properties;
        $this->getIntegrationProperties();
        $this->getConnectionProperties();
    }

    /**
     * @param string $hash
     *
     * @throws ComposerException
     */
    public function get($hash): array
    {
        if (!isset($this->propertyList[$hash])) {
            throw new ComposerException(
                $this->translator->translate("Could not find properties for key '{hash}'", ['hash' => $hash])
            );
        }

        return $this->propertyList[$hash];
    }

    /**
     * @param string $hash
     * @param array  $params
     *
     * @throws ComposerException
     */
    public function set($hash, $params): array
    {
        if (isset($this->propertyList[$hash])) {
            $this->removeHash($hash);
        }

        return $this->propertyList[$hash] = $params;
    }

    /**
     * @param int $index
     *
     * @throws ComposerException
     */
    public function getPageProperties($index): PageProperties
    {
        $pageHash = self::PAGE_PREFIX.$index;
        if (!isset($this->builtProperties[$pageHash])) {
            if (!isset($this->propertyList[$pageHash])) {
                throw new ComposerException(
                    $this->translator->translate("Could not find properties for page '{index}'", ['index' => $index])
                );
            }

            $this->builtProperties[$pageHash] = new PageProperties($this->propertyList[$pageHash], $this->translator);
        }

        return $this->builtProperties[$pageHash];
    }

    /**
     * @param string $hash
     *
     * @throws ComposerException
     */
    public function getFieldProperties($hash): FieldProperties
    {
        if (!isset($this->builtProperties[$hash])) {
            if (!isset($this->propertyList[$hash])) {
                throw new ComposerException(
                    $this->translator->translate("Could not find properties for field '{hash}'", ['hash' => $hash])
                );
            }

            $properties = $this->propertyList[$hash];
            $properties['hash'] = $hash;

            $this->builtProperties[$hash] = new FieldProperties($properties, $this->translator);
        }

        return $this->builtProperties[$hash];
    }

    /**
     * @throws ComposerException
     */
    public function getFormProperties(): FormProperties
    {
        $hash = self::FORM_HASH;
        if (!isset($this->builtProperties[$hash])) {
            if (!isset($this->propertyList[$hash])) {
                throw new ComposerException(
                    $this->translator->translate('Could not find properties for form')
                );
            }

            $this->builtProperties[$hash] = new FormProperties($this->propertyList[$hash], $this->translator);
        }

        return $this->builtProperties[$hash];
    }

    /**
     * @throws ComposerException
     */
    public function getValidationProperties(): ValidationProperties
    {
        $hash = self::VALIDATION_HASH;
        if (!isset($this->builtProperties[$hash])) {
            if (!isset($this->propertyList[$hash])) {
                $this->propertyList[$hash] = [];
            }

            $this->builtProperties[$hash] = new ValidationProperties($this->propertyList[$hash], $this->translator);
        }

        return $this->builtProperties[$hash];
    }

    /**
     * @throws ComposerException
     */
    public function getAdminNotificationProperties(): AdminNotificationProperties
    {
        $hash = self::ADMIN_NOTIFICATIONS_HASH;
        if (!isset($this->builtProperties[$hash])) {
            if (!isset($this->propertyList[$hash])) {
                throw new ComposerException(
                    $this->translator->translate('Could not find properties for admin notifications')
                );
            }

            $this->builtProperties[$hash] = new AdminNotificationProperties($this->propertyList[$hash], $this->translator);
        }

        return $this->builtProperties[$hash];
    }

    /**
     * @throws ComposerException
     */
    public function getIntegrationProperties(): IntegrationProperties
    {
        $hash = self::INTEGRATION_HASH;
        if (!isset($this->builtProperties[$hash])) {
            if (!isset($this->propertyList[$hash])) {
                throw new ComposerException(
                    $this->translator->translate('Could not find properties for integrations')
                );
            }

            $this->builtProperties[$hash] = new IntegrationProperties($this->propertyList[$hash], $this->translator);
        }

        return $this->builtProperties[$hash];
    }

    /**
     * @throws ComposerException
     */
    public function getPaymentProperties(): PaymentProperties
    {
        $hash = self::PAYMENT_HASH;
        if (!isset($this->builtProperties[$hash])) {
            if (!isset($this->propertyList[$hash])) {
                throw new ComposerException(
                    $this->translator->translate('Could not find properties for integrations')
                );
            }

            $this->builtProperties[$hash] = new PaymentProperties($this->propertyList[$hash], $this->translator);
        }

        return $this->builtProperties[$hash];
    }

    public function getConnectionProperties(): ConnectionProperties
    {
        $hash = self::CONNECTIONS_HASH;
        if (!isset($this->builtProperties[$hash])) {
            if (isset($this->propertyList[$hash])) {
                $settings = $this->propertyList[$hash];
            } else {
                $settings = ['type' => 'connections', 'list' => []];
            }

            $this->builtProperties[$hash] = new ConnectionProperties($settings, $this->translator);
        }

        return $this->builtProperties[$hash];
    }

    /**
     * @return null|RuleProperties
     */
    public function getRuleProperties()
    {
        if (!Freeform::getInstance()->isPro()) {
            return null;
        }

        $hash = self::RULES_HASH;
        if (!isset($this->builtProperties[$hash])) {
            if (isset($this->propertyList[$hash])) {
                $settings = $this->propertyList[$hash];
            } else {
                $settings = ['type' => 'rules', 'fields' => null, 'pages' => null];
            }

            $this->builtProperties[$hash] = new RuleProperties($settings, $this->translator, $this);
        }

        return $this->builtProperties[$hash];
    }

    /**
     * @param string $hash
     */
    public function removeHash($hash)
    {
        if (isset($this->propertyList[$hash])) {
            unset($this->propertyList[$hash]);
        }
        if (isset($this->builtProperties[$hash])) {
            unset($this->builtProperties[$hash]);
        }
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see  http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $properties = $this->propertyList;
        array_walk_recursive(
            $properties,
            function (&$value, $key) {
                if (null === $value) {
                    $value = null;
                } elseif (\is_string($value) && !\in_array($key, [
                    'value',
                    'label',
                    'handle',
                    'description',
                ], true) && preg_match('/^(true|false)$/i', $value)) {
                    $value = 'true' === strtolower($value);
                }
            }
        );

        return $properties;
    }
}
