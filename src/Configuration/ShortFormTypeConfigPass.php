<?php

namespace AlterPHP\EasyAdminExtensionBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigPassInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Generalization of short form types for :
 *     - EasyAdminExtension bundle types
 *     - Custom form types
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
class ShortFormTypeConfigPass implements ConfigPassInterface
{
    private $customFormTypes = array();

    private static $configWithFormPaths = array('[form][fields]', '[edit][fields]', '[new][fields]', '[list][form_filters]');
    private static $nativeShortFormTypes = array(
        'embedded_list' => 'AlterPHP\EasyAdminExtensionBundle\Form\Type\EasyAdminEmbeddedListType',
        'admin_roles' => 'AlterPHP\EasyAdminExtensionBundle\Form\Type\Security\AdminRolesType',
    );

    public function __construct(array $customFormTypes = array())
    {
        $this->customFormTypes = $customFormTypes;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->replaceShortNameTypes($backendConfig);

        return $backendConfig;
    }

    private function replaceShortNameTypes(array $backendConfig)
    {
        if (isset($backendConfig['entities']) && is_array($backendConfig['entities'])) {
            foreach ($backendConfig['entities'] as &$entityConfig) {
                $entityConfig = $this->replaceShortFormTypesInObjectConfig($entityConfig);
            }
        }

        if (isset($backendConfig['documents']) && is_array($backendConfig['documents'])) {
            foreach ($backendConfig['documents'] as &$documentConfig) {
                $documentConfig = $this->replaceShortFormTypesInObjectConfig($documentConfig);
            }
        }

        return $backendConfig;
    }

    private function replaceShortFormTypesInObjectConfig(array $objectConfig)
    {
        $shortFormTypes = $this->getShortFormTypes();

        foreach (static::$configWithFormPaths as $configWithFormPath) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $configPathItem = $propertyAccessor->getValue($objectConfig, $configWithFormPath);

            if (null !== $configPathItem && is_array($configPathItem)) {
                foreach ($configPathItem as $name => $field) {
                    if (!isset($field['type'])) {
                        continue;
                    }

                    if (in_array($field['type'], array_keys($shortFormTypes))) {
                        $configPathItem[$name]['type'] = $shortFormTypes[$field['type']];
                    } elseif (self::isLegacyEasyAdminFormShortType($field['type'])) {
                        $configPathItem[$name]['type'] = LegacyFormHelper::getType($field['type']);
                    }
                }

                $propertyAccessor->setValue($objectConfig, $configWithFormPath, $configPathItem);
            }

            unset($propertyAccessor);
        }

        return $objectConfig;
    }

    private static function isLegacyEasyAdminFormShortType(string $shortType)
    {
        $legacyEasyAdminMatchingType = LegacyFormHelper::getType($shortType);

        return class_exists($legacyEasyAdminMatchingType);
    }

    private function getShortFormTypes()
    {
        return array_merge(static::$nativeShortFormTypes, $this->customFormTypes);
    }
}
