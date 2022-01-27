<?php

namespace Drupal\node_singles\Twig\Extension;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node_singles\Service\NodeSingles;
use Drupal\node_singles\Service\NodeSinglesInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SingleExtension extends AbstractExtension
{
    /** @var RendererInterface */
    protected $renderer;
    /** @var NodeSinglesInterface */
    protected $singles;

    public function __construct(
        RendererInterface $renderer,
        NodeSingles $singles
    ) {
        $this->renderer = $renderer;
        $this->singles = $singles;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('single', [$this, 'getSingle']),
        ];
    }

    public function getSingle($bundle)
    {
        if (!is_string($bundle)) {
            return null;
        }

        $entity = $this->singles->getSingleByBundle($bundle);

        // Workaround to include caching metadata of the single entity
        if ($entity instanceof EntityInterface) {
            $build = [];
            CacheableMetadata::createFromObject($entity)
                ->applyTo($build);
            $this->renderer->render($build);
        }

        return $entity;
    }
}
