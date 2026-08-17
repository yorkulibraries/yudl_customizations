<?php

namespace Drupal\yudl_regenesis\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for finding and repairing missing Islandora derivatives.
 *
 * Covers media of bundle "image" whose field_media_image file is missing
 * on the local filesystem, for the "Thumbnail Image" and "Service File"
 * media-use terms. Repairs are done by invoking the same actions available
 * in the "Missing Media" views bulk operations, but from the command line.
 */
class YudlRegenesisCommands extends DrushCommands {

  /**
   * Media-use label => action plugin ID used to regenerate that derivative.
   *
   * @var array
   */
  protected const USE_ACTION_MAP = [
    'Thumbnail Image' => 'image_generate_a_thumbnail_from_an_original_file',
    'Service File' => 'image_generate_a_service_file_from_an_original_file',
  ];

  /**
   * The field on the "image" media bundle holding the derivative file.
   */
  protected const FILE_FIELD = 'field_media_image';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Constructs the command class.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    AccountSwitcherInterface $account_switcher,
  ) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * Scan for image/service-file media whose file is missing on disk.
   *
   * Writes results to a CSV. Does not modify anything. Run this first,
   * review the CSV, then use yudl:fire-missing-derivatives to repair.
   *
   * @param array $options
   *   Command options.
   *
   * @option csv
   *   Path to write the results CSV to.
   * @option scheme
   *   Only check files using this stream wrapper scheme (e.g. "public").
   *   Files using other schemes (e.g. "fedora") are skipped and reported
   *   separately, since realpath()/file_exists() cannot reliably check
   *   remote/virtual stream wrappers.
   *
   * @command yudl:regenesis-scan
   * @aliases yudl-rs
   * @usage drush yudl:yudl:regenesis-scan --csv=/tmp/broken.csv
   */
  public function scanMissingDerivatives(
    array $options = [
      'csv' => NULL,
      'scheme' => 'public',
    ],
  ) {
    $csv_path = $options['csv'] ?: '/tmp/yudl-broken_derivatives-' . date('Ymd-His') . '.csv';
    $scheme = rtrim($options['scheme'], ':/') . '://';

    $media_storage = $this->entityTypeManager->getStorage('media');

    $mids = $media_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'image')
      ->execute();

    $total = count($mids);
    $this->logger()->notice("Scanning $total image media entities...");

    $fh = fopen($csv_path, 'w');
    if ($fh === FALSE) {
      throw new \RuntimeException("Could not open $csv_path for writing. Check directory permissions for the user running drush.");
    }
    fputcsv($fh, ['mid', 'nid', 'use', 'fid', 'uri']);

    $checked = 0;
    $broken_count = 0;
    $skipped_scheme_count = 0;
    $summary = [];

    foreach (array_chunk($mids, 500) as $chunk) {
      $medias = $media_storage->loadMultiple($chunk);

      foreach ($medias as $media) {
        $checked++;

        $use_label = NULL;
        foreach ($media->get('field_media_use') as $item) {
          if ($item->entity && isset(self::USE_ACTION_MAP[$item->entity->label()])) {
            $use_label = $item->entity->label();
            break;
          }
        }
        if (!$use_label || $media->get(self::FILE_FIELD)->isEmpty()) {
          continue;
        }

        $file = $media->get(self::FILE_FIELD)->entity;
        if (!$file) {
          continue;
        }

        $uri = $file->getFileUri();

        // Only reliably check local schemes (default: public://). Remote
        // wrappers like fedora:// cannot be checked with file_exists().
        if (strpos($uri, $scheme) !== 0) {
          $skipped_scheme_count++;
          continue;
        }

        $realpath = $this->fileSystem->realpath($uri);
        $missing = !$realpath || !file_exists($realpath);

        if ($missing && !$media->get('field_media_of')->isEmpty()) {
          $nid = $media->get('field_media_of')->target_id;
          $broken_count++;
          $summary[$use_label] = ($summary[$use_label] ?? 0) + 1;
          fputcsv($fh, [$media->id(), $nid, $use_label, $file->id(), $uri]);
        }
      }

      $media_storage->resetCache($chunk);

      if ($checked % 20000 === 0) {
        $this->logger()->notice("Checked $checked / $total ... $broken_count broken so far");
      }
    }

    fclose($fh);

    $this->logger()->notice('--- Summary ---');
    foreach ($summary as $use_label => $count) {
      $this->logger()->notice("$use_label: $count broken");
    }
    if ($skipped_scheme_count > 0) {
      $this->logger()->warning("$skipped_scheme_count media used a non-'$scheme' URI and were skipped (cannot verify existence for remote/virtual schemes).");
    }
    $this->logger()->success("Full list written to $csv_path");
    $this->logger()->notice('Review the CSV, then run: drush yudl:fire-missing-derivatives --csv=' . $csv_path);
  }

  /**
   * Fire the regenerate action for each broken media/node in a scan CSV.
   *
   * Requires a readable JWT signing key. This command verifies the key can
   * actually be read (not just that it's configured) before firing any
   * actions, since a silently-unreadable key results in every action
   * appearing to run while all downstream derivative generation fails.
   *
   * @param array $options
   *   Command options.
   *
   * @option csv
   *   Path to the CSV produced by yudl:scan-missing-derivatives.
   * @option uid
   *   The uid to run the actions as (needed for JWT/message signing).
   * @option key-id
   *   The Key module entity ID used for JWT signing, to verify readability.
   *
   * @command yudl:regenesis-generate
   * @aliases yudl-rg
   * @usage drush yudl:regenesis-generate --csv=/tmp/broken.csv --uid=1
   */
  public function fireMissingDerivatives(
    array $options = [
      'csv' => NULL,
      'uid' => 1,
      'key-id' => NULL,
    ],
  ) {
    $csv_path = $options['csv'];
    if (!$csv_path || !file_exists($csv_path)) {
      throw new \RuntimeException('A valid --csv path is required. Run yudl:scan-missing-derivatives first.');
    }

    // Verify the JWT signing key is actually readable in this process
    // context before firing anything. A silently-empty key means every
    // action below will appear to run while the derivative-generation
    // microservice message fails to sign.
    $this->assertJwtKeyReadable($options['key-id']);

    $node_storage = $this->entityTypeManager->getStorage('node');
    $action_storage = $this->entityTypeManager->getStorage('action');

    $broken_nodes = [];
    $fh = fopen($csv_path, 'r');
    // Skip header row.
    fgetcsv($fh);

    $row_count = 0;
    while (($row = fgetcsv($fh)) !== FALSE) {
      $row_count++;
      [$mid, $nid, $use] = $row;

      if (!isset(self::USE_ACTION_MAP[$use])) {
        $this->logger()->warning("Row $row_count: unrecognized use '$use' - skipping");
        continue;
      }

      $broken_nodes[self::USE_ACTION_MAP[$use]][] = (int) $nid;
    }
    fclose($fh);

    $this->logger()->notice("Loaded $row_count rows from CSV.");
    foreach ($broken_nodes as $action_id => $nids) {
      $this->logger()->notice("$action_id: " . count(array_unique($nids)) . ' node(s)');
    }

    if (!$this->io()->confirm('Proceed with firing these actions?', TRUE)) {
      $this->logger()->notice('Aborted.');
      return;
    }

    $user = User::load((int) $options['uid']);
    if (!$user) {
      throw new \RuntimeException("Could not load user {$options['uid']}.");
    }
    $this->accountSwitcher->switchTo($user);
    $this->logger()->notice("Switched to user {$user->id()} ({$user->getAccountName()})");

    try {
      foreach ($broken_nodes as $action_id => $nids) {
        $action = $action_storage->load($action_id);
        if (!$action) {
          $this->logger()->warning("Action $action_id not found - skipping");
          continue;
        }

        $unique_nids = array_values(array_unique($nids));
        $total = count($unique_nids);
        $done = 0;

        foreach (array_chunk($unique_nids, 50) as $nid_chunk) {
          $nodes = $node_storage->loadMultiple($nid_chunk);
          $action->getPlugin()->executeMultiple($nodes);
          $node_storage->resetCache($nid_chunk);
          $done += count($nodes);
          $this->logger()->notice("Fired $action_id on $done / $total node(s)");
        }
      }
    }
    finally {
      $this->accountSwitcher->switchBack();
      $this->logger()->notice('Switched back to previous user context.');
    }

    $this->logger()->success('Done.');
  }

  /**
   * Verify the configured JWT signing key is actually readable.
   *
   * The jwt module's key can be correctly configured (a valid key entity
   * pointing at a real file) yet still return an empty value if the
   * current process user cannot traverse the directories leading to the
   * key file. That failure is silent at the Key-module level and only
   * surfaces later as "Error getting JWT token" once actions are fired -
   * after nodes have already been queued. This check catches it up front.
   *
   * @param string|null $key_id
   *   The key entity ID to check. If NULL, reads jwt.config's key_id.
   *
   * @throws \RuntimeException
   *   If the key cannot be loaded or its value cannot be read.
   */
  protected function assertJwtKeyReadable(?string $key_id = NULL): void {
    if (!$key_id) {
      $key_id = \Drupal::config('jwt.config')->get('key_id');
    }

    if (!$key_id) {
      throw new \RuntimeException('No JWT signing key is configured (jwt.config:key_id is empty). Set one up before firing derivative actions.');
    }

    $key = $this->entityTypeManager->getStorage('key')->load($key_id);
    if (!$key) {
      throw new \RuntimeException("JWT key entity '$key_id' not found.");
    }

    try {
      $value = $key->getKeyValue();
    }
    catch (\Exception $e) {
      throw new \RuntimeException("Could not read JWT key '$key_id': " . $e->getMessage());
    }

    if (empty($value)) {
      $provider_config = $key->getKeyProvider()->getConfiguration();
      $location = $provider_config['file_location'] ?? $provider_config['key_location'] ?? '(unknown path)';
      throw new \RuntimeException(
        "JWT key '$key_id' loaded but its value is empty when read as the current process user. " .
        "This usually means a parent directory of the key file (configured at: $location) is not " .
        "traversable by this user (check with: namei -l $location). Derivative-generation actions " .
        "will silently fail with 'Error getting JWT token' if fired now. Common fix: run this command " .
        "as the web server user, e.g.: sudo -u www-data drush yudl:fire-missing-derivatives ..."
      );
    }

    $this->logger()->notice("JWT key '$key_id' is readable (" . strlen($value) . ' bytes). Proceeding.');
  }

}
