<?php

namespace Drupal\yudl_map\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

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
   * Return node geolocation data as JSON.
   *
   * Optimized for large datasets (11k+ nodes) by querying only
   * the necessary database fields.
   */
public function mapData() {
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
    if ($row->field_coordinates_lat === null || $row->field_coordinates_lng === null) {
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

  return new JsonResponse($data);
}
}
