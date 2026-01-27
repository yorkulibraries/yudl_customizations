<?php

namespace Drupal\yudl_oembed\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class OEmbedBuilder {

  private const DEFAULT_PROVIDER_NAME = 'York University Digital Library';
  private const DEFAULT_PROVIDER_URL = 'https://digital.library.yorku.ca';
  private const DEFAULT_WIDTH = 560;
  private const DEFAULT_HEIGHT = 315;
  private const DEFAULT_THUMBNAIL_WIDTH = 480;
  private const DEFAULT_THUMBNAIL_HEIGHT = 360;

  private ConfigFactoryInterface $configFactory;
  private RequestStack $requestStack;
  private EntityTypeManagerInterface $entityTypeManager;
  private FileUrlGeneratorInterface $fileUrlGenerator;

  public function __construct(
    ConfigFactoryInterface $configFactory,
    RequestStack $requestStack,
    EntityTypeManagerInterface $entityTypeManager,
    FileUrlGeneratorInterface $fileUrlGenerator
  ) {
    $this->configFactory = $configFactory;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * Builds an oEmbed response payload for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to embed.
   * @param array $options
   *   Optional settings: width, height.
   *
   * @return array<string, mixed>
   *   The oEmbed response.
   */
  public function build(NodeInterface $node, array $options = []): array {
    $width = $this->normalizeDimension($options['width'] ?? NULL, self::DEFAULT_WIDTH);
    $height = $this->normalizeDimension($options['height'] ?? NULL, self::DEFAULT_HEIGHT);
    $thumbnail_width = $this->normalizeDimension($options['thumbnail_width'] ?? NULL, self::DEFAULT_THUMBNAIL_WIDTH);
    $thumbnail_height = $this->normalizeDimension($options['thumbnail_height'] ?? NULL, self::DEFAULT_THUMBNAIL_HEIGHT);
    $thumbnail_dimensions_provided = !empty($options['thumbnail_dimensions_provided']);

    $provider_name = $this->configFactory->get('system.site')->get('name');
    if (!is_string($provider_name) || $provider_name === '') {
      $provider_name = self::DEFAULT_PROVIDER_NAME;
    }

    $provider_url = $this->getProviderUrl();
    $embed_url = Url::fromUri('internal:/embed/' . $node->id(), ['absolute' => TRUE])->toString();
    $media_entities = $this->loadMediaForNode($node);
    $type = $this->getOEmbedTypeFromMedia($media_entities);
    $thumbnail_url = $options['thumbnail_url'] ?? '';
    if (!is_string($thumbnail_url)) {
      $thumbnail_url = '';
    }
    if ($thumbnail_url === '') {
      $thumbnail = $this->getThumbnailFromMediaEntities($media_entities);
      if ($thumbnail) {
        $thumbnail_url = $thumbnail['url'] ?? '';
        if (!$thumbnail_dimensions_provided) {
          $thumbnail_width = $this->normalizeDimension($thumbnail['width'] ?? NULL, $thumbnail_width);
          $thumbnail_height = $this->normalizeDimension($thumbnail['height'] ?? NULL, $thumbnail_height);
        }
      }
    }
    if ($thumbnail_url === '') {
      $thumbnail_url = Url::fromUri('internal:/node/' . $node->id() . '/thumbnail', ['absolute' => TRUE])->toString();
    }
    if (str_starts_with($thumbnail_url, '/')) {
      $thumbnail_url = Url::fromUri('internal:' . $thumbnail_url, ['absolute' => TRUE])->toString();
    }

    $html = sprintf(
      '<iframe width="%d" height="%d" src="%s" frameborder="0" allowfullscreen></iframe>',
      $width,
      $height,
      $embed_url
    );

    $author_name = '';
    $owner = $node->getOwner();
    if ($owner) {
      $author_name = $owner->getDisplayName();
    }

    return [
      'type' => $type,
      'version' => '1.0',
      'provider_name' => $provider_name,
      'provider_url' => $provider_url,
      'title' => $node->getTitle(),
      'author_name' => $author_name,
      'html' => $html,
      'width' => $width,
      'height' => $height,
      'thumbnail_url' => $thumbnail_url,
      'thumbnail_width' => $thumbnail_width,
      'thumbnail_height' => $thumbnail_height,
    ];
  }

  private function getProviderUrl(): string {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $host = $request->getSchemeAndHttpHost();
      if (is_string($host) && $host !== '') {
        return $host;
      }
    }

    return self::DEFAULT_PROVIDER_URL;
  }

  private function getThumbnailFromMediaEntities(array $media_entities): ?array {
    if (!$media_entities) {
      return NULL;
    }

    $image_media = [];
    foreach ($media_entities as $media_entity) {
      if ($media_entity instanceof MediaInterface && $media_entity->bundle() === 'image') {
        $image_media[] = $media_entity;
      }
    }

    if (!$image_media) {
      return NULL;
    }

    $media = $this->selectThumbnailMedia($image_media);
    if (!$media || !$media->hasField('field_media_image') || $media->get('field_media_image')->isEmpty()) {
      return NULL;
    }

    $image_item = $media->get('field_media_image')->first();
    $file = $image_item?->entity;
    if (!$file) {
      return NULL;
    }

    $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    return [
      'url' => $url,
      'width' => $image_item?->width,
      'height' => $image_item?->height,
    ];
  }

  /**
   * @return \Drupal\media\MediaInterface[]
   */
  private function loadMediaForNode(NodeInterface $node): array {
    try {
      $media_storage = $this->entityTypeManager->getStorage('media');
      $media_ids = $media_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('field_media_of', $node->id())
        ->execute();
    }
    catch (\Throwable $exception) {
      return [];
    }

    if (!$media_ids) {
      return [];
    }

    sort($media_ids);
    return array_values($media_storage->loadMultiple($media_ids));
  }

  private function getOEmbedTypeFromMedia(array $media_entities): string {
    if (!$media_entities) {
      return 'rich';
    }

    $has_type = [
      'video' => FALSE,
      'photo' => FALSE,
      'link' => FALSE,
    ];

    foreach ($media_entities as $media_entity) {
      if (!$media_entity instanceof MediaInterface) {
        continue;
      }

      switch ($media_entity->bundle()) {
        case 'video':
        case 'remote_video':
          $has_type['video'] = TRUE;
          break;
        case 'image':
          $has_type['photo'] = TRUE;
          break;
        case 'document':
        case 'file':
        case 'extracted_text':
          $has_type['link'] = TRUE;
          break;
      }
    }

    if ($has_type['video']) {
      return 'video';
    }
    if ($has_type['photo']) {
      return 'photo';
    }
    if ($has_type['link']) {
      return 'link';
    }

    return 'rich';
  }

  /**
   * @param \Drupal\media\MediaInterface[] $media_entities
   */
  private function selectThumbnailMedia(array $media_entities): ?MediaInterface {
    if (!$media_entities) {
      return NULL;
    }

    $candidates = array_values($media_entities);
    foreach ($candidates as $media) {
      if ($this->hasThumbnailUse($media)) {
        return $media;
      }
    }

    return $candidates[0] ?? NULL;
  }

  private function hasThumbnailUse(MediaInterface $media): bool {
    if (!$media->hasField('field_media_use') || $media->get('field_media_use')->isEmpty()) {
      return FALSE;
    }

    $term_ids = array_column($media->get('field_media_use')->getValue(), 'target_id');
    if (!$term_ids) {
      return FALSE;
    }

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($term_ids);
    foreach ($terms as $term) {
      if (stripos($term->label(), 'thumbnail') !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
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

}
