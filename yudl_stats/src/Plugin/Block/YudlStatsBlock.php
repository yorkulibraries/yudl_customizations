<?php

namespace Drupal\yudl_stats\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YudlStatsBlock' block.
 *
 * @Block(
 *  id = "yudl_stats_block",
 *  admin_label = @Translation("Yudl stats block"),
 * )
 */
class YudlStatsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'yudl_stats_block';
     $build['yudl_stats_block']['#markup'] = 'Implement YudlStatsBlock.';

    return $build;
  }

}
