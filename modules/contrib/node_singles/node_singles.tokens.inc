<?php

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\node_singles\Service\NodeSinglesInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Implements @see hook_token_info().
 */
function node_singles_token_info(): array
{
    $types['node_singles'] = [
        'name' => t('Node Singles'),
        'description' => t('Node Singles tokens'),
    ];

    // Get all singles.
    $singles = [];
    /** @var EntityTypeInterface $type */
    foreach (\Drupal::service('node_singles')->getAllSingles() as $type) {
        $singles[$type->id() . ':url'] = [
            'name' => $type->label() . ' URL',
            'description' => t('The alias of the node single.'),
        ];
    }

    return [
        'types' => $types,
        'tokens' => [
            'node_singles' => $singles,
        ],
    ];
}

/**
 * Implements @see hook_tokens().
 */
function node_singles_tokens(string $type, array $tokens, array $data = [], array $options = []): array
{
    $replacements = [];

    if ($type !== 'node_singles') {
        return $replacements;
    }

    /** @var NodeSinglesInterface $singles */
    $singles = \Drupal::service('node_singles');
    /** @var AliasManagerInterface $pathAliasManager */
    $pathAliasManager = \Drupal::service('path_alias.manager');

    foreach ($tokens as $name => $original) {
        $split = explode(':', $name);

        // Load the content type
        $node = $singles->getSingleByBundle($split[0]);

        if (!$node instanceof NodeInterface) {
            continue;
        }

        if ($split[1] === 'url') {
            $canonical = '/node/' . $node->id();
            $alias = $pathAliasManager->getAliasByPath($canonical, $options['langcode']);

            if ($alias !== $canonical) {
                $replace = $alias;
            }
        }

        $replacements[$original] = $replace ?? '';
    }

    return $replacements;
}
