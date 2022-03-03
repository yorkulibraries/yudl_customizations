<?php

namespace Drupal\yu_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YuLibFooterCenter' Block.
 *
 * @Block(
 *   id = "yu_lib_footer_center_block",
 *   admin_label = @Translation("YU Library Footer Center"),
 *   category = @Translation("YU Library"),
 * )
 */
class YuLibFooterCenterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $yu_footer = '<p>
        <span class="font-weight-bold">Keele Campus</span><br />
          4700 Keele Street, Toronto<br />
          ON Canada<br />
          M3J 1P3<br />
          <a href="tel:4167362100" class="text-white">(416) 736-2100</a>
      </p>';

    $build['yu_lib_footer_center_block']['#markup'] = $yu_footer;
    return $build;
  }
}
