<?php

namespace Drupal\yudl_oembed\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class EmbedController extends ControllerBase {

  private EntityDisplayRepositoryInterface $displayRepository;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->displayRepository = $display_repository;
  }

  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  public function view(NodeInterface $node): array {
    $view_mode = $this->resolveViewMode($node);
    $build = $this->entityTypeManager->getViewBuilder('node')->view($node, $view_mode);
    $this->allowFraming($build);
    return $build;
  }

  private function resolveViewMode(NodeInterface $node): string {
    $options = $this->displayRepository->getViewModeOptionsByBundle('node', $node->bundle());
    if (isset($options['oembed'])) {
      return 'oembed';
    }
    if (isset($options['embed'])) {
      return 'embed';
    }
    return 'full';
  }

  private function allowFraming(array &$build): void {
    $build['#attached']['http_header'][] = ['X-Frame-Options', 'ALLOWALL', TRUE];
    $build['#attached']['http_header'][] = ['Content-Security-Policy', 'frame-ancestors *', TRUE];
  }

}
