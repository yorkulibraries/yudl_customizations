<?php

namespace Drupal\yudl_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'CollectionInfo' Block.
 *
 * @Block(
 *   id = "yudl_collection_info_block",
 *   admin_label = @Translation("Collection information block"),
 *   category = @Translation("Collection"),
 * )
 */

class CollectionInfoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }
