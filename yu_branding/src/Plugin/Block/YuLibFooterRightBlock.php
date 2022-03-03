<?php

namespace Drupal\yu_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YuLibFooterRight' Block.
 *
 * @Block(
 *   id = "yu_lib_footer_right_block",
 *   admin_label = @Translation("YU Library Footer Right"),
 *   category = @Translation("YU Library"),
 * )
 */
class YuLibFooterRightBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $yu_footer = '<ul>
        <li><a href="https://www.yorku.ca/safety/" class="text-white">Community Safety</a></li>
        <li><a href="https://map.concept3d.com/?id=1200#!s/?ct/29101,29093" class="text-white">Campus Maps</a></li>
        <li><a href="https://www.yorku.ca/about/privacy-legal/" class="text-white">Privacy &amp; Legal</a></li>
        <li><a href="https://accessibility.students.yorku.ca/" class="text-white">Accessibility</a></li>
        <li><a href="https://www.yorku.ca/about/careers/" class="text-white">Careers</a></li>
      </ul>';

    $build['yu_lib_footer_right_block']['#markup'] = $yu_footer;
    return $build;
  }
}
