<?php

namespace Drupal\yu_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YuLibFooter' Block.
 *
 * @Block(
 *   id = "yu_lib_footer_block",
 *   admin_label = @Translation("YU Library Footer"),
 *   category = @Translation("YU Library"),
 * )
 */
class YuLibFooterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $logo_url = 'https://www.library.yorku.ca/web/wp-content/themes/york2020/images/yorku-logo-ko.png';

    $footer01 = '<div>
        <h2 class="h6 text-white text-center">Connect with York University Libraries</h2>
      </div>
      <div>
        <a href="https://www.facebook.com/YorkUniversityLibraries" aria-label="Facebook: https://www.facebook.com/YorkUniversityLibraries"  class=""><i class="fa-brands fa-facebook"></i></a>
        <a href="https://twitter.com/yorkulibraries" aria-label="Twitter: https://twitter.com/yorkulibraries"  class=""><i class="fa-brands fa-twitter"></i></a>
        <a href="https://www.instagram.com/yorkulibraries/" aria-label="Instagram: https://www.instagram.com/yorkulibraries/"  class=""><i class="fa-brands fa-instagram"></i></a>
	<a href="https://www.youtube.com/user/yorkulibraries/videos" aria-label="YouTube: https://www.youtube.com/user/yorkulibraries/videos"  class=""><i class="fa-brands fa-youtube"></i></a>
     </div>';

    $footer02 = '<div class="container pt-5" style="background:url(\'https://www.library.yorku.ca/web/wp-content/themes/york2020/images/arrow.png\');">
      <div class="row pb-3">
        <div class="col-md-3">
          <a href="https://www.yorku.ca"><img src="https://www.library.yorku.ca/web/wp-content/themes/york2020/images/yorku-logo-ko.png" alt="York University" class="w-100 mb-4"></a>
        </div>
      <div class="col-md-3">
        <p class="small text-white">
          <span class="font-weight-bold">Keele Campus</span><br />
          4700 Keele Street, Toronto<br />
          ON Canada<br />
          M3J 1P3<br />
          <a href="tel:4167362100" class="text-white">(416) 736-2100</a>
        </p>
      </div>
      <div class="col-md-6">
        <ul class="list-unstyled small">
          <li><a href="https://www.yorku.ca/safety/" class="text-white">Community Safety</a></li>
          <li><a href="https://map.concept3d.com/?id=1200#!s/?ct/29101,29093" class="text-white">Campus Maps</a></li>
          <li><a href="https://www.yorku.ca/about/privacy-legal/" class="text-white">Privacy &amp; Legal</a></li>
          <li><a href="https://accessibility.students.yorku.ca/" class="text-white">Accessibility</a></li>
          <li><a href="https://www.yorku.ca/about/careers/" class="text-white">Careers</a></li>
        </ul>
      </div>
      </div>';

    $build['yu_lib_footer_block']['#markup'] = $footer01 . $footer02;
    return $build;
  }
}
