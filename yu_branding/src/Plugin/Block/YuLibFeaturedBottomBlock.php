<?php

namespace Drupal\yu_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YuLibFeaturedBottom' Block.
 *
 * @Block(
 *   id = "yu_lib_featured_bottom_block",
 *   admin_label = @Translation("YU Library Featured Bottom"),
 *   category = @Translation("YU Library"),
 * )
 */
class YuLibFeaturedBottomBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $yu_featured_bottom = '<div class="wp-block-group">
        <div class="wp-block-group__inner-container">
          <div class="wp-block-columns social-bar-block">
            <div class="wp-block-column">
              <h2 class="h6 text-white text-center">Connect with York University Libraries</h2>
            </div>
            <div class="wp-block-column">
	      <ul class="wp-block-social-links aligncenter my-2 is-style-default">
                <a href="https://www.facebook.com/YorkUniversityLibraries" aria-label="Facebook: https://www.facebook.com/YorkUniversityLibraries"  class=""><i class="fa-brands fa-facebook"></i></a>
                <a href="https://twitter.com/yorkulibraries" aria-label="Twitter: https://twitter.com/yorkulibraries"  class=""><i class="fa-brands fa-twitter"></i></a>
                <a href="https://www.instagram.com/yorkulibraries/" aria-label="Instagram: https://www.instagram.com/yorkulibraries/"  class=""><i class="fa-brands fa-instagram"></i></a>
                <a href="https://www.youtube.com/user/yorkulibraries/videos" aria-label="YouTube: https://www.youtube.com/user/yorkulibraries/videos"  class=""><i class="fa-brands fa-youtube"></i></a>
            </ul>
          </div>
        </div>
      </div>
    </div>';

    $build['yu_lib_featured_bottom_block']['#markup'] = $yu_featured_bottom;
    return $build;
  }
}
