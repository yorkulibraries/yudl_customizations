<?php

namespace Drupal\yudl_map\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Controller for the YUDL Leaflet map.
 */
class MapController extends ControllerBase {

  /**
   * Render the Leaflet map page.
   */
  public function mapPage() {
    return [
      '#theme' => 'yudl_map',
      '#attached' => [
        'library' => [
          'yudl_map/yudl_map_assets',
          'geolocation_leaflet/mapfeature.leaflet_marker_clusterer',
          'geolocation_leaflet/leaflet.fullscreen',
        ],
      ],
    ];
  }

  /**
   * Returns geolocation data for Islandora objects as JSON.
   *
   * This endpoint provides a lightweight JSON representation of node
   * geolocation data used to render markers in the Leaflet-based map view.
   * Only the required database fields are queried to avoid loading full
   * node entities, which improves performance for large datasets.
   *
   * Results are cached using Drupal's cache API to reduce database load.
   * The cached dataset is invalidated when nodes change via the `node_list`
   * cache tag and expires automatically after 24 hours.
   *
   * The response is returned as a CacheableJsonResponse so that Drupal's
   * render caching and downstream caches (e.g., reverse proxies or CDNs)
   * can reuse the response efficiently.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   A JSON response containing an array of objects with the following keys:
   *   - nid: Node ID.
   *   - title: Node title.
   *   - lat: Latitude of the geolocation field.
   *   - lng: Longitude of the geolocation field.
   *   - url: Canonical URL to the node.
   */
  public function mapData() {

    $cid = 'yudl_map:node_coordinates';

    // Check Drupal cache first.
    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
    }
    else {

      $connection = \Drupal::database();

      $query = $connection->select('node__field_coordinates', 'fc');
      $query->join('node_field_data', 'n', 'n.nid = fc.entity_id');
      $query->fields('fc', ['field_coordinates_lat', 'field_coordinates_lng']);
      $query->fields('n', ['nid', 'title']);
      $query->condition('n.type', 'islandora_object');
      $query->condition('fc.deleted', 0);
      $query->orderBy('n.nid', 'ASC');

      $result = $query->execute()->fetchAll();

      $data = [];

      foreach ($result as $row) {
        if ($row->field_coordinates_lat === NULL || $row->field_coordinates_lng === NULL) {
          continue;
        }

        $data[] = [
          'nid' => $row->nid,
          'title' => $row->title,
          'lat' => (float) $row->field_coordinates_lat,
          'lng' => (float) $row->field_coordinates_lng,
          'url' => '/node/' . $row->nid,
        ];
      }

      // Cache the result for 1 day.
      \Drupal::cache()->set($cid, $data, time() + 86400, ['node_list']);
    }

    // Return a cacheable JSON response.
    $response = new CacheableJsonResponse($data);

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->setCacheTags(['node_list']);
    $cache_metadata->setCacheMaxAge(86400);

    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

}
