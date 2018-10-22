<?php

namespace AlterPHP\EasyAdminExtensionBundle\Twig;

use AlterPHP\EasyAdminMongoOdmBundle\Configuration\ConfigManager as MongoOdmConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager as EntityConfigManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EasyAdminExtensionTwigExtension extends AbstractExtension
{
    private $entityConfigManager;
    private $mongoOdmConfigManager;
    private $router;

    public function __construct(
        RouterInterface $router, EntityConfigManager $entityConfigManager, MongoOdmConfigManager $mongoOdmConfigManager = null
    ) {
        $this->entityConfigManager = $entityConfigManager;
        $this->mongoOdmConfigManager = $mongoOdmConfigManager;
        $this->router = $router;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('easyadmin_base_twig_path', array($this, 'getBaseTwigPath')),
            new TwigFunction('easyadmin_object', array($this, 'getObjectConfiguration')),
            new TwigFunction('easyadmin_object_type', array($this, 'getObjectType')),
            new TwigFunction('easyadmin_path', array($this, 'getEasyAdminPath')),
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

        // Fallback not entity/document admin pages based on EasyAdmin layout ?
        return sprintf('@BaseEasyAdmin/%s', $path);
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
     * Returns the given object type.
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

    /**
     * Returns easyadmin path for given parameters.
     *
     * @param array $parameters
     *
     * @return string
     */
    public function getEasyAdminPath(array $parameters)
    {
        if (array_key_exists('entity', $parameters)) {
            return $this->router->generate('easyadmin', $parameters);
        } elseif (array_key_exists('document', $parameters)) {
            return $this->router->generate('easyadmin_mongo_odm', $parameters);
        }

        throw new \RuntimeException('Parameters must contain either "entity" or "document" key !');
    }
}
