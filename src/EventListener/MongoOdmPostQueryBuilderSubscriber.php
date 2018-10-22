<?php

namespace AlterPHP\EasyAdminExtensionBundle\EventListener;

use AlterPHP\EasyAdminMongoOdmBundle\Event\EasyAdminMongoOdmEvents;
use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Apply filters on list/search queryBuilder.
 */
class MongoOdmPostQueryBuilderSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            EasyAdminMongoOdmEvents::POST_LIST_QUERY_BUILDER => array('onPostListQueryBuilder'),
            EasyAdminMongoOdmEvents::POST_SEARCH_QUERY_BUILDER => array('onPostSearchQueryBuilder'),
        );
    }

    /**
     * Called on POST_LIST_QUERY_BUILDER event.
     *
     * @param GenericEvent $event
     */
    public function onPostListQueryBuilder(GenericEvent $event)
    {
        $queryBuilder = $event->getArgument('query_builder');

        if ($event->hasArgument('request')) {
            $this->applyRequestFilters($queryBuilder, $event->getArgument('request')->get('filters', array()));
            $this->applyFormFilters($queryBuilder, $event->getArgument('request')->get('form_filters', array()));
        }
    }

    /**
     * Called on POST_SEARCH_QUERY_BUILDER event.
     *
     * @param GenericEvent $event
     */
    public function onPostSearchQueryBuilder(GenericEvent $event)
    {
        $queryBuilder = $event->getArgument('query_builder');

        if ($event->hasArgument('request')) {
            $this->applyRequestFilters($queryBuilder, $event->getArgument('request')->get('filters', array()));
        }
    }

    /**
     * Applies request filters on queryBuilder.
     *
     * @param QueryBuilder $queryBuilder
     * @param array        $filters
     */
    protected function applyRequestFilters(QueryBuilder $queryBuilder, array $filters = array())
    {
        foreach ($filters as $field => $value) {
            // Empty string and numeric keys is considered as "not applied filter"
            if (is_int($field) || '' === $value) {
                continue;
            }
            // Checks if filter is directly appliable on queryBuilder
            if (!$this->isFilterAppliable($queryBuilder, $field)) {
                continue;
            }

            $this->filterQueryBuilder($queryBuilder, $field, $value);
        }
    }

    /**
     * Applies form filters on queryBuilder.
     *
     * @param QueryBuilder $queryBuilder
     * @param array        $filters
     */
    protected function applyFormFilters(QueryBuilder $queryBuilder, array $filters = array())
    {
        foreach ($filters as $field => $value) {
            $value = $this->filterEasyadminAutocompleteValue($value);
            // Empty string and numeric keys is considered as "not applied filter"
            if (is_int($field) || '' === $value) {
                continue;
            }
            // Checks if filter is directly appliable on queryBuilder
            if (!$this->isFilterAppliable($queryBuilder, $field)) {
                continue;
            }

            $this->filterQueryBuilder($queryBuilder, $field, $value);
        }
    }

    private function filterEasyadminAutocompleteValue($value)
    {
        if (!is_array($value) || !isset($value['autocomplete']) || 1 !== count($value)) {
            return $value;
        }

        return $value['autocomplete'];
    }

    /**
     * Filters queryBuilder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $field
     * @param mixed        $value
     */
    protected function filterQueryBuilder(QueryBuilder $queryBuilder, string $field, $value)
    {
        // For multiple value, use an IN clause, equality otherwise
        if (is_array($value)) {
            $filterExpr = $queryBuilder->expr()->field($field)->in($value);
        } elseif ('_NULL' === $value) {
            // Matches documents where field is NULL or does not exist
            $filterExpr = $queryBuilder->expr()->field($field)->equals(null);
        } elseif ('_NOT_NULL' === $value) {
            $filterExpr = $queryBuilder->expr()->field($field)->notEqual(null);
        } else {
            $filterExpr = $queryBuilder->expr()->field($field)->equals($value);
        }

        $queryBuilder->addAnd($filterExpr);
    }

    /**
     * Checks if filter is directly appliable on queryBuilder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $field
     *
     * @return bool
     */
    protected function isFilterAppliable(QueryBuilder $queryBuilder, string $field): bool
    {
        return true;
    }
}
