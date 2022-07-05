<?php

namespace Drupal\yu_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'YuLibHeader' Block.
 *
 * @Block(
 *   id = "yu_lib_header_block",
 *   admin_label = @Translation("YU Library Header"),
 *   category = @Translation("YU Library"),
 * )
 */
class YuLibHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $yu_header = '<div id="branding" class="container">
        <div class="d-sm-flex d-block justify-content-between py-3">
          <div id="site-title" class="d-inline-block">
            <a href="https://www.yorku.ca"><img src="https://www.library.yorku.ca/web/wp-content/themes/york2020/images/yorku-logo.jpg" alt="York University" class="yu-logo"></a>
          </div>
          <div class="d-flex align-items-center">
            <div id="yul-wpml-dropdown" class="px-2">
              <a class="btn btn-outline-dark rounded-0" href="/fr" class="">French (Français)</a></li>
            </div>
            <div id="popular-links" class="dropdown pt-2 pt-md-0">
              <button class="btn btn-outline-dark dropdown-toggle rounded-0 w-100" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Popular Links  </button>
              <div class="dropdown-menu shadow-sm border border-dark w-sm-100" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="https://futurestudents.yorku.ca/program-search/">Programs of Study</a>
                <a class="dropdown-item" href="https://eclass.yorku.ca/">eClass</a>
                <a class="dropdown-item" href="https://www.yorku.ca/about/email/">Email</a>
                <a class="dropdown-item" href="https://www.yorku.ca/about/faculty-staff/">Faculty &amp; Staff</a>
                <a class="dropdown-item" href="https://sfs.yorku.ca/">Financial Services</a>
                <a class="dropdown-item" href="https://www.library.yorku.ca/web/">Libraries</a>
                <a class="dropdown-item" href="https://atlas.yorku.ca/">Directory</a>
              </div>
            </div>
            <button class="btn btn-link ml-4 text-dark" data-toggle="modal" data-target="#searchModal"><i class="fas fa-search"></i><span class="sr-only">Search</span></button>
          </div>
        </div>
      </div>';

    $build['yu_lib_header_block']['#markup'] = $yu_header;
    return $build;
  }
}
