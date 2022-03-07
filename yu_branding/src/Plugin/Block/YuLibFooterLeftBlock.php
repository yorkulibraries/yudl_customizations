<?php

namespace Drupal\yu_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YuLibFooterLeft' Block.
 *
 * @Block(
 *   id = "yu_lib_footer_left_block",
 *   admin_label = @Translation("YU Library Footer Left"),
 *   category = @Translation("YU Library"),
 * )
 */
class YuLibFooterLeftBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $yu_footer = '<a href="https://www.yorku.ca"><img src="https://www.library.yorku.ca/web/wp-content/themes/york2020/images/yorku-logo-ko.png" alt="York University" class="w-100 mb-4"></a>';

    $build['yu_lib_footer_left_block']['#markup'] = $yu_footer;
    return $build;
  }
}
