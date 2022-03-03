<?php

namespace Drupal\yu_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YuLibSubHeader' Block.
 *
 * @Block(
 *   id = "yu_lib_sub_header_block",
 *   admin_label = @Translation("YU Library Sub-Header"),
 *   category = @Translation("YU Library"),
 * )
 */
class YuLibSubHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $yu_sub_header = '<div class="navbar navbar-expand-lg navbar-dark bg-primary py-1">
        <div class="container">
          <a class="navbar-brand" href="https://digital.library.yorku.ca" rel="home">York University Digital Library</a>
        </div>
      </div>';

    $build['yu_lib_sub_header_block']['#markup'] = $yu_sub_header;
    return $build;
  }
}
