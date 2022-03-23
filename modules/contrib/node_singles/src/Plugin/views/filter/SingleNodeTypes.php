<?php

namespace Drupal\node_singles\Plugin\views\filter;

use Drupal\node_singles\Service\NodeSinglesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ViewsFilter("node_singles")
 */
class SingleNodeTypes extends LimitBundle
{
    /** @var NodeSinglesInterface */
    protected $singles;

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id, $plugin_definition
    ) {
        $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
        $instance->singles = $container->get('node_singles');

        return $instance;
    }

    protected function getBundles(): array
    {
        return array_keys($this->singles->getAllSingles());
    }
}
