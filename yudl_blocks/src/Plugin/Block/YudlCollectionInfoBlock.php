<?php

namespace Drupal\yudl_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides a 'CollectionInfo' Block.
 *
 * @Block(
 *   id = "yudl_collection_info_block",
 *   admin_label = @Translation("YUDL Collection information block"),
 *   category = @Translation("Collection"),
 * )
 */
class YudlCollectionInfoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new NodeTranslationLanguagesBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    CurrentRouteMatch $currentRouteMatch
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('current_route_match')
    );
  }

  /**
   * Makes the markup for a given item's box for the template.
   *
   * @param string $string
   *   The inside text string for the box that will be linked.
   * @param bool $is_large_content
   *   Is the content large <-- Luis.
   *
   * @return string
   *   Markup of the box for use in the template
   */
  private function makeBox($string, bool $is_large_content = FALSE) {
    if ($is_large_content) {
      return '<div class="stats_box col-md-6 col-sm-12 g-2"><div class="card card-stats mb-4 mb-xl-0"><div class="card-body">' . $string . '</div></div></div>';
    }
    else {
      return '<div class="stats_box"><div class="card card-stats mb-4 mb-xl-0"><div class="card-body">' . $string . '</div></div></div>';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $total_languages = $islandora_models = $items = 0;
    $stat_box_row1 = $stat_box_row2 = [];
    $collection_node = $this->currentRouteMatch->getParameter('node');
    $children = yudl_blocks_solr_get_collection_children($collection_node);
    if (array_key_exists('item_count', $children)) {
      $items = $children['item_count'];
    }
    if (array_key_exists('model_count', $children)) {
      $islandora_models = $children['model_count'];
    }
    if (array_key_exists('collection_created', $children)) {
      $collection_created = $children['collection_created'];
    }
    if (array_key_exists('collection_recently_added', $children)) {
      $last_change_date = $children['collection_recently_added'];
    }
    if (array_key_exists('collection_languages', $children)) {
      $total_languages = $children['collection_languages'];
    }

    $stat_box_row1[] = $this->makeBox('
                        <div class="row">
                          <div class="col"><h3 class="h5 card-title text-uppercase text-muted mb-0">Items</h3><span class="h2 text-primary font-weight-bold mb-0">' . number_format($items - 1) . '</span></div>' .
                          '<div class="col-auto"><div class="icon icon-shape rounded-circle"><i class="fa-solid fa-layer-group fs-4"></i></div></div>' .
                        '</div>');
    $stat_box_row1[] = $this->makeBox('
                        <div class="row">
                          <div class="col"><h3 class="h5 card-title text-uppercase text-muted mb-0">Resource Types</h3><span class="h2 text-primary font-weight-bold mb-0">' . number_format($islandora_models - 1) . '</span></div>' .
                          '<div class="col-auto"><div class="icon icon-shape rounded-circle"><i class="fas fa-shapes fs-4"></i></div></div>' .
                          '</div>');
    $stat_box_row1[] = $this->makeBox('
                        <div class="row">
                          <div class="col"><h3 class="h5 card-title text-uppercase text-muted mb-0">Unique Languages</h3><span class="h2 text-primary font-weight-bold mb-0">' . number_format($total_languages) . '</span></div>' .
                          '<div class="col-auto"><div class="icon icon-shape rounded-circle"><i class="fa-solid fa-language fs-4"></i></div></div>' .
                          '</div>');
    $stat_box_row1[] = $this->makeBox('
                        <div class="row">
                          <div class="col"><h3 class="h5 card-title text-uppercase text-muted mb-0">Collection Created</h3><span class="h2 text-primary font-weight-bold mb-0">' . (($collection_created) ? yudl_blocks_format_time($collection_created) : 'unknown') . '</span></div>' .
                          '<div class="col-auto"><div class="icon icon-shape rounded-circle"><i class="fas fa-crown fs-4"></i></div></div>' .
                          '</div>');
    $stat_box_row1[] = $this->makeBox('
                        <div class="row">
                          <div class="col"><h3 class="h5 card-title text-uppercase text-muted mb-0">Most Recent Item Added</h3><span class="h2 text-primary font-weight-bold mb-0">' . (($last_change_date) ? yudl_blocks_format_time($last_change_date) : 'unknown') . '</span></div>' .
                          '<div class="col-auto"><div class="icon icon-shape rounded-circle"><i class="fas fa-clock fs-4"></i></div></div>' .
                        '</div>');

    return [
      '#markup' =>
      (count($stat_box_row1) > 0) ?
        // ROW 1.
      '<div class="stats-container"><div class="row row-cols-1 row-cols-md-3 py-2 g-md-3 mb-2">' .
      implode('', $stat_box_row1) .
      '</div>' .
        // ROW 2.
      '<div class="row">' .
      implode('', $stat_box_row2) .
      '</div>' :
      "",
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
