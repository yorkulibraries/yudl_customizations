<?php

namespace Drupal\yudl_oembed\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\yudl_oembed\Service\OEmbedBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

final class OEmbedController extends ControllerBase {

  private const DEFAULT_MAX_WIDTH = 560;
  private const DEFAULT_MAX_HEIGHT = 315;

  private PathValidatorInterface $pathValidator;
  private AliasManagerInterface $aliasManager;
  protected $entityTypeManager;
  private OEmbedBuilder $builder;
  private RequestStack $requestStack;

  public function __construct(
    PathValidatorInterface $path_validator,
    AliasManagerInterface $alias_manager,
    EntityTypeManagerInterface $entity_type_manager,
    OEmbedBuilder $builder,
    RequestStack $request_stack
  ) {
    $this->pathValidator = $path_validator;
    $this->aliasManager = $alias_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->builder = $builder;
    $this->requestStack = $request_stack;
  }

  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('path.validator'),
      $container->get('path_alias.manager'),
      $container->get('entity_type.manager'),
      $container->get('yudl_oembed.builder'),
      $container->get('request_stack'),
    );
  }

  public function handle(): Response {
    $request = $this->requestStack->getCurrentRequest();
    $url = $request ? $request->query->get('url') : NULL;

    if (!is_string($url) || $url === '') {
      return $this->errorResponse('Missing url parameter.', 400);
    }

    if (!UrlHelper::isValid($url, TRUE) && !UrlHelper::isValid($url, FALSE)) {
      return $this->errorResponse('Invalid url parameter.', 400);
    }

    $path = $this->extractPath($url);
    if ($path === '') {
      return $this->errorResponse('Invalid url parameter.', 400);
    }

    $path = '/' . ltrim($path, '/');
    $path = $this->aliasManager->getPathByAlias($path);
    $route_url = $this->pathValidator->getUrlIfValid($path);
    if (!$route_url || !$route_url->isRouted() || $route_url->getRouteName() !== 'entity.node.canonical') {
      return $this->errorResponse('Resource not found.', 404);
    }

    $route_parameters = $route_url->getRouteParameters();
    $node_id = $route_parameters['node'] ?? NULL;
    if (!$node_id) {
      return $this->errorResponse('Resource not found.', 404);
    }

    $node = $this->entityTypeManager->getStorage('node')->load($node_id);
    if (!$node || !$node->access('view')) {
      return $this->errorResponse('Resource not found.', 404);
    }

    $width_param = $request ? $request->query->get('maxwidth') : NULL;
    $height_param = $request ? $request->query->get('maxheight') : NULL;
    $width = $this->normalizeDimension($width_param, self::DEFAULT_MAX_WIDTH);
    $height = $this->normalizeDimension($height_param, self::DEFAULT_MAX_HEIGHT);
    $thumbnail_url = $request ? $request->query->get('thumbnail_url') : NULL;
    $thumbnail_width = $request ? $request->query->get('thumbnail_width') : NULL;
    $thumbnail_height = $request ? $request->query->get('thumbnail_height') : NULL;
    $thumbnail_dimensions_provided = $thumbnail_width !== NULL || $thumbnail_height !== NULL;

    $payload = $this->builder->build($node, [
      'width' => $width,
      'height' => $height,
      'thumbnail_url' => $thumbnail_url,
      'thumbnail_width' => $thumbnail_width,
      'thumbnail_height' => $thumbnail_height,
      'thumbnail_dimensions_provided' => $thumbnail_dimensions_provided,
    ]);

    return $this->jsonResponse($payload, 200);
  }

  private function extractPath(string $url): string {
    $parsed = parse_url($url);
    if (is_array($parsed) && !empty($parsed['path'])) {
      return $parsed['path'];
    }

    return '';
  }

  private function normalizeDimension($value, int $fallback): int {
    if (is_numeric($value)) {
      $value = (int) $value;
      if ($value > 0) {
        return $value;
      }
    }

    return $fallback;
  }

  private function errorResponse(string $message, int $status): JsonResponse {
    return $this->jsonResponse(['error' => $message], $status);
  }

  private function jsonResponse(array $payload, int $status): JsonResponse {
    $response = new JsonResponse($payload, $status);
    $response->headers->set('Content-Type', 'application/json+oembed');
    return $response;
  }

}
