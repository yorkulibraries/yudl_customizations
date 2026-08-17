# YUDL Regenesis

Find and repair Islandora Image media (Thumbnail Image or Service File) whose file exists in the database but is missing on disk.

## Commands

### `drush yudl:regenesis-scan`

Scans all `image` media for the "Thumbnail Image" and "Service File" use terms, and checks if the referenced file actually exists on disk. Writes any broken ones to a CSV.

```
drush yudl:regenesis-scan --csv=/tmp/broken.csv
```

Options:
- `--csv` - output path (default: `/tmp/yudl-broken_derivatives-<timestamp>.csv`)

### `drush yudl:regenesis-generate`

Reads a CSV created by the scan command and fires a corresponding action "Generate a thumbnail" or "Generate a service file" action on each node.

```
drush yudl:regenesis-generate --csv=/tmp/broken.csv --uid=1
```

Options:
- `--csv` — required, path to the scan CSV
- `--uid` — Drupal user to run the actions as (default: 1)
- `--key-id` — override the Key entity ID to verify (default: reads
  `jwt.config:key_id`)

## Installation

```
drush en yudl_regenesis
```
