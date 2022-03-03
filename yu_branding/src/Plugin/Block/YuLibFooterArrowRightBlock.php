<?php

namespace Drupal\yu_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YuLibFooterArrowRight' Block.
 *
 * @Block(
 *   id = "yu_lib_footer_arrow_right_block",
 *   admin_label = @Translation("YU Library Footer Arrow"),
 *   category = @Translation("YU Library"),
 * )
 */
class YuLibFooterArrowRightBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $yu_footer = '<img src="https://www.library.yorku.ca/web/wp-content/themes/york2020/images/arrow.png" />';

    $build['yu_lib_footer_arrow_right_block']['#markup'] = $yu_footer;
    return $build;
  }
}
