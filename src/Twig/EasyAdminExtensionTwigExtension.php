<?php

namespace AlterPHP\EasyAdminExtensionBundle\Twig;

use AlterPHP\EasyAdminMongoOdmBundle\Configuration\ConfigManager as MongoOdmConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager as EntityConfigManager;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EasyAdminExtensionTwigExtension extends AbstractExtension
{
    private $entityConfigManager;
    private $mongoOdmConfigManager;

    public function __construct(EntityConfigManager $entityConfigManager, MongoOdmConfigManager $mongoOdmConfigManager = null)
    {
        $this->entityConfigManager = $entityConfigManager;
        $this->mongoOdmConfigManager = $mongoOdmConfigManager;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('easyadmin_base_twig_path', array($this, 'getBaseTwigPath')),
            new TwigFunction('easyadmin_object', array($this, 'getObjectConfiguration')),
            new TwigFunction('easyadmin_object_type', array($this, 'getObjectType')),
        );
    }

    /**
     * Returns the namespaced base Twig path.
     *
     * @param Request $request
     * @param string  $path
     *
     * @return string
     */
    public function getBaseTwigPath(Request $request, string $path)
    {
        $requestRoute = $request->attributes->get('_route');

        if ('easyadmin' === $requestRoute && $request->query->has('entity')) {
            return sprintf('@BaseEasyAdmin/%s', $path);
        } elseif ('easyadmin_mongo_odm' === $requestRoute && $request->query->has('document')) {
            return sprintf('@BaseEasyAdminMongoOdm/%s', $path);
        }

        // Fallback is useful ?
        return sprintf('@EasyAdminExtension/%s', $path);
    }

    /**
     * Returns the entire configuration of the given object.
     *
     * @param Request $request
     *
     * @return array|null
     */
    public function getObjectConfiguration(Request $request)
    {
        $requestRoute = $request->attributes->get('_route');

        if ('easyadmin' === $requestRoute && $request->query->has('entity')) {
            return $this->entityConfigManager->getEntityConfig($request->query->get('entity'));
        }

        if (null === $this->mongoOdmConfigManager) {
            return null;
        }

        if ('easyadmin_mongo_odm' === $requestRoute && $request->query->has('document')) {
            return $this->mongoOdmConfigManager->getDocumentConfig($request->query->get('document'));
        }

        return null;
    }

    /**
     * Returns the the given object type.
     *
     * @param Request $request
     *
     * @return string|null
     */
    public function getObjectType(Request $request)
    {
        $requestRoute = $request->attributes->get('_route');

        if ('easyadmin' === $requestRoute && $request->query->has('entity')) {
            return 'entity';
        }

        if (null === $this->mongoOdmConfigManager) {
            return null;
        }

        if ('easyadmin_mongo_odm' === $requestRoute && $request->query->has('document')) {
            return 'document';
        }

        return null;
    }
}
