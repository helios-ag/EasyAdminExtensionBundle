<?php

namespace AlterPHP\EasyAdminExtensionBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AdminAuthorizationExtension extends AbstractExtension
{
    protected $adminAuthorizationChecker;
    protected $menuHelper;

    public function __construct($adminAuthorizationChecker, $menuHelper)
    {
        $this->adminAuthorizationChecker = $adminAuthorizationChecker;
        $this->menuHelper = $menuHelper;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('prune_item_actions', array($this, 'pruneItemsActions')),
            new TwigFilter('prune_menu_items', array($this, 'pruneMenuItems')),
        );
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('is_easyadmin_granted', array($this, 'isEasyAdminGranted')),
        );
    }

    public function isEasyAdminGranted(array $entityConfig, string $actionName = 'list', $subject = null)
    {
        return $this->adminAuthorizationChecker->isEasyAdminGranted($entityConfig, $actionName, $subject);
    }

    public function pruneItemsActions(
        array $itemActions, array $entityConfig, array $forbiddenActions = [], $subject = null
    ) {
        return array_filter($itemActions, function ($action) use ($entityConfig, $forbiddenActions, $subject) {
            return !in_array($action, $forbiddenActions)
                    && $this->isEasyAdminGranted($entityConfig, $action, $subject)
            ;
        }, ARRAY_FILTER_USE_KEY);
    }

    public function pruneMenuItems(array $menuConfig, array $entitiesConfig)
    {
        return $this->menuHelper->pruneMenuItems($menuConfig, $entitiesConfig);
    }
}
