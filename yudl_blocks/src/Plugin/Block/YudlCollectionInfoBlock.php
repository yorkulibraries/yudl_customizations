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
      return '<div class="stats_box col-6"><div class="stats_border_box">' . $string . '</div></div>';
    }
    else {
      return '<div class="stats_box col-4"><div class="stats_border_box">' . $string . '</div></div>';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $islandora_models = $items = 0;
    $stat_box_row1 = $stat_box_row2 = [];
    $collection_node = $this->currentRouteMatch->getParameter('node');
    $collection_created = $collection_node->get('revision_timestamp')->getString();

    $total_languages = get_languages_per_node($collection_node, $this->entityTypeManager);
    $last_change_date = get_latest_changed_node($this->entityTypeManager);
    $children = yudl_blocks_solr_get_collection_children($collection_node);

    if (array_key_exists('item_count', $children)) {
      $items = $children['item_count'];
    }
    if (array_key_exists('model_count', $children)) {
      $islandora_models = $children['model_count'];
    }

    $stat_box_row1[] = $this->makeBox("<strong>" . number_format($items) . "</strong><br>items");
    $stat_box_row1[] = $this->makeBox("<strong>" . number_format($islandora_models) . "</strong><br>resource types");
    $stat_box_row1[] = $this->makeBox("<strong>" . number_format($total_languages) . "</strong><br>unique languages");
    $stat_box_row2[] = $this->makeBox("<strong>" . (($collection_created) ? format_time($collection_created) : 'unknown') .
      "</strong><br>collection created", TRUE);
    $stat_box_row2[] = $this->makeBox("<strong>" . (($last_change_date) ? $last_change_date : 'unknown') .
      "</strong><br>most recent item added</div>", TRUE);
    return [
      '#markup' =>
      (count($stat_box_row1) > 0) ?
        // ROW 1.
      '<div class="container"><div class="row">' .
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
