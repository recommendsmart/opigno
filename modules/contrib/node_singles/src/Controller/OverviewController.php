<?php

namespace Drupal\node_singles\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeTypeInterface;
use Drupal\node_singles\Service\NodeSinglesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OverviewController implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var NodeSinglesInterface */
    protected $singles;

    public static function create(ContainerInterface $container)
    {
        $instance = new static;
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->singles = $container->get('node_singles');

        return $instance;
    }

    public function overview(): array
    {
        $output['table'] = [
            '#type' => 'table',
            '#header' => [
                $this->t('Name'),
                $this->t('Description'),
                $this->t('Operations'),
            ],
            '#empty' => $this->t('No singles found.'),
            '#sticky' => true,
        ];

        /** @var NodeTypeInterface $item */
        foreach ($this->singles->getAllSingles() as $item) {
            $node = $this->singles->getSingleByBundle($item->id());

            if ($node) {
                $operations = $this->entityTypeManager->getListBuilder('node')->getOperations($node);

                $output['table'][$item->id()]['title'] = [
                    '#markup' => sprintf(
                        '<a href="%s">%s</a>',
                        Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString(),
                        $node->label() ?: $item->label()
                    ),
                ];

                $output['table'][$item->id()]['description'] = [
                    '#plain_text' => $item->getDescription(),
                ];

                $output['table'][$item->id()]['operations'] = [
                    '#type' => 'operations',
                    '#subtype' => 'node',
                    '#links' => $operations,
                ];
            }
        }

        return $output;
    }
}
