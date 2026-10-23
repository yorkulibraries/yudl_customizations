<?php

namespace Drupal\yudl_search\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Index field_member_of as the nearest collection(s) a node belongs to.
 *
 * Parents that aren't collections (e.g. a newspaper issue a page belongs to)
 * are replaced by their nearest collection ancestors, so nested items are
 * indexed as members of their collection.
 * Cribbed from https://github.com/catalyst/cca_search
 *
 * @SearchApiProcessor(
 *   id = "member_of_collection",
 *   label = @Translation("Member of collection"),
 *   description = @Translation("Index field_member_of values as the nearest collection ancestors of the node"),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class MemberOfCollection extends ProcessorPluginBase {

  /**
   * Model URIs that count as a collection.
   */
  const COLLECTION_MODEL_URIS = [
    'http://purl.org/dc/dcmitype/Collection',
    'https://schema.org/Newspaper',
  ];

  /**
   * Maximum number of levels to walk up looking for a collection.
   */
  const MAX_DEPTH = 10;

  /**
   * Cache of nid => [is_collection, parent nids], shared across batches.
   *
   * @var array
   */
  protected static $nodeInfo = [];

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      // Get node being indexed.
      $object = $item->getOriginalObject();
      $node = $object->getValue();
      if ($node->hasField('field_member_of') && !$node->field_member_of->isEmpty()) {
        $parents = array_column($node->get('field_member_of')->getValue(), 'target_id');
        // Modify node with nearest collection ancestors, perhaps [].
        $node->set('field_member_of', $this->findCollections($parents));
        // Replace original object with modified $node value.
        $object->setValue($node);
        $items[$item_id]->setOriginalObject($object);
      }
    }
  }

  /**
   * Walks up field_member_of from the given parents to the nearest collections.
   *
   * A collection parent is kept as is; a non-collection parent is replaced by
   * its own nearest collection ancestors.
   *
   * @param array $parents
   *   Parent node ids.
   *
   * @return array
   *   Node ids of the nearest collection ancestors.
   */
  protected function findCollections(array $parents) {
    $collections = [];
    $visited = [];
    $level = $parents;
    for ($depth = 0; $level && $depth < static::MAX_DEPTH; $depth++) {
      $next = [];
      foreach ($level as $nid) {
        if (isset($visited[$nid])) {
          continue;
        }
        $visited[$nid] = TRUE;
        $info = $this->getNodeInfo($nid);
        if ($info === NULL) {
          continue;
        }
        if ($info['is_collection']) {
          $collections[$nid] = $nid;
        }
        else {
          $next = array_merge($next, $info['parents']);
        }
      }
      $level = $next;
    }
    return array_values($collections);
  }

  /**
   * Gets whether a node is a collection, and its parents.
   *
   * @param int|string $nid
   *   Node id.
   *
   * @return array|null
   *   Array with 'is_collection' and 'parents' keys, or NULL if the node
   *   doesn't exist.
   */
  protected function getNodeInfo($nid) {
    if (array_key_exists($nid, static::$nodeInfo)) {
      return static::$nodeInfo[$nid];
    }
    $info = NULL;
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if (is_object($node)) {
      $info = [
        'is_collection' => FALSE,
        'parents' => [],
      ];
      if ($node->hasField('field_model') && ($model_term = $node->get('field_model')->entity)) {
        $uri = $model_term->get('field_external_uri')->first();
        $info['is_collection'] = $uri && in_array($uri->getUrl()->getUri(), static::COLLECTION_MODEL_URIS);
      }
      if ($node->hasField('field_member_of')) {
        $info['parents'] = array_column($node->get('field_member_of')->getValue(), 'target_id');
      }
    }
    static::$nodeInfo[$nid] = $info;
    return $info;
  }

}
