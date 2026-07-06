#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

DATABASE_NAME="${DATABASE_NAME:-articulate_test}"
DATABASE_USER="${DATABASE_USER:-user}"
DATABASE_PASSWORD="${DATABASE_PASSWORD:-userpassword}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-rootpassword}"

FEATURES=(
  "Catalog"
  "CustomerAccounts"
  "Orders"
  "Tagging"
  "Analytics"
  "BulkImport"
)

YES=0
STRICT=0

usage() {
  cat <<'EOF'
Usage: tools/generate-migrations.sh [--yes] [--strict]

Generates driver-specific Articulate migrations for MySQL and PostgreSQL.

The script:
  1. Starts Docker services.
  2. Removes and recreates the target database.
  3. Replaces migrations/mysql and migrations/pgsql.
  4. Generates migrations feature-by-feature in dependency order.
  5. Renames each generated migration class/file to include the feature name.
  6. Applies each generated migration before moving to the next feature.
  7. Updates Markdown links if they point at renamed migration files.

Options:
  --yes         Do not prompt before replacing migration directories/databases.
  --strict      Fail when a feature produces no migration.
  -h, --help    Show this help.
EOF
}

while (($#)); do
  case "$1" in
    --yes)
      YES=1
      ;;
    --strict)
      STRICT=1
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      usage >&2
      exit 2
      ;;
  esac
  shift
done

confirm_destructive_work() {
  if ((YES)); then
    return
  fi

  cat <<EOF
This will replace:
  - migrations/mysql
  - migrations/pgsql

It will also drop and recreate database "$DATABASE_NAME" in the MySQL and PostgreSQL containers.
EOF
  read -r -p "Continue? [y/N] " answer
  case "$answer" in
    y|Y|yes|YES)
      ;;
    *)
      echo "Aborted."
      exit 1
      ;;
  esac
}

log() {
  printf '\n==> %s\n' "$*"
}

compose() {
  docker compose "$@"
}

php_console() {
  local driver="$1"
  shift

  compose exec -T \
    -e "DATABASE_NAME=$DATABASE_NAME" \
    -e "DATABASE_USER=$DATABASE_USER" \
    -e "DATABASE_PASSWORD=$DATABASE_PASSWORD" \
    -e "DATABASE_DSN=$(dsn_for "$driver")" \
    -e "ARTICULATE_MIGRATIONS_PATH=migrations/$driver" \
    php bin/console "$@" --no-interaction
}

php_shell() {
  local driver="$1"
  shift

  compose exec -T \
    -e "DATABASE_NAME=$DATABASE_NAME" \
    -e "DATABASE_USER=$DATABASE_USER" \
    -e "DATABASE_PASSWORD=$DATABASE_PASSWORD" \
    -e "DATABASE_DSN=$(dsn_for "$driver")" \
    -e "ARTICULATE_MIGRATIONS_PATH=migrations/$driver" \
    php sh -lc "$*"
}

debug_relations() {
  if [[ "${DEBUG_RELATIONS:-0}" != "1" ]]; then
    return
  fi

  compose exec -T php php <<'PHP'
<?php

require 'vendor/autoload.php';

use Articulate\Attributes\Reflection\ReflectionEntity;

$root = new RecursiveDirectoryIterator('src');
$files = new RecursiveIteratorIterator($root);

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $contents = file_get_contents($file->getPathname());
    if ($contents === false) {
        continue;
    }

    if (!preg_match('/namespace\s+(.+?);/', $contents, $namespaceMatches)
        || !preg_match('/class\s+(\w+)/', $contents, $classMatches)) {
        continue;
    }

    $class = $namespaceMatches[1] . '\\' . $classMatches[1];
    if (!class_exists($class)) {
        continue;
    }

    $entity = new ReflectionEntity($class);
    if (!$entity->isEntity()) {
        continue;
    }

    echo "ENTITY $class\n";
    foreach ($entity->getEntityRelationProperties() as $relation) {
        echo '  RELATION ' . $relation::class . ' -> ';
        try {
            var_export(method_exists($relation, 'getTargetEntity') ? $relation->getTargetEntity() : null);
        } catch (Throwable $throwable) {
            echo get_class($throwable) . ': ' . $throwable->getMessage();
        }
        echo "\n";
    }
}
PHP
}

dsn_for() {
  case "$1" in
    mysql)
      printf 'mysql:host=mysql;dbname=%s;charset=utf8mb4' "$DATABASE_NAME"
      ;;
    pgsql)
      printf 'pgsql:host=pgsql;dbname=%s' "$DATABASE_NAME"
      ;;
    *)
      echo "Unsupported driver: $1" >&2
      exit 2
      ;;
  esac
}

start_containers() {
  log "Starting containers"
  compose up -d mysql pgsql php
}

reset_mysql_database() {
  log "Resetting MySQL database"
  compose exec -T mysql mysql \
    -uroot \
    "-p$MYSQL_ROOT_PASSWORD" \
    -e "DROP DATABASE IF EXISTS \`$DATABASE_NAME\`; CREATE DATABASE \`$DATABASE_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON \`$DATABASE_NAME\`.* TO '$DATABASE_USER'@'%'; FLUSH PRIVILEGES;"
}

reset_pgsql_database() {
  log "Resetting PostgreSQL database"
  compose exec -T \
    -e "PGPASSWORD=$DATABASE_PASSWORD" \
    pgsql psql \
    -U "$DATABASE_USER" \
    -d postgres \
    -v ON_ERROR_STOP=1 \
    -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$DATABASE_NAME' AND pid <> pg_backend_pid();" \
    -c "DROP DATABASE IF EXISTS \"$DATABASE_NAME\";" \
    -c "CREATE DATABASE \"$DATABASE_NAME\" OWNER \"$DATABASE_USER\";"
}

clear_migration_directory() {
  local driver="$1"

  log "Replacing migrations/$driver"
  collect_old_migration_links "$driver"
  rm -rf "migrations/$driver"
  mkdir -p "migrations/$driver"
}

clear_symfony_cache() {
  local driver="$1"

  php_shell "$driver" 'rm -rf var/cache/dev var/cache/test'
}

entity_files=()
backup_dir=""
old_migration_links_file=""

collect_entity_files() {
  local file

  entity_files=()
  while IFS= read -r file; do
    entity_files+=("$file")
  done < <(grep -RIl '^[[:space:]]*#\[Entity' src 2>/dev/null || true)
}

backup_entities() {
  backup_dir="$(mktemp -d "${TMPDIR:-/tmp}/articulate-entities.XXXXXX")"
  for file in "${entity_files[@]}"; do
    mkdir -p "$backup_dir/$(dirname "$file")"
    cp "$file" "$backup_dir/$file"
  done
}

restore_entities() {
  if [[ -z "$backup_dir" || ! -d "$backup_dir" ]]; then
    return
  fi

  for file in "${entity_files[@]}"; do
    if [[ -f "$backup_dir/$file" ]]; then
      cp "$backup_dir/$file" "$file"
    fi
  done

  rm -rf "$backup_dir"
  backup_dir=""
}

collect_old_migration_links() {
  local driver="$1"
  local feature
  local old_file

  if [[ ! -d "migrations/$driver" ]]; then
    return
  fi

  for feature in "${FEATURES[@]}"; do
    old_file="$(find "migrations/$driver" -type f -name "Migration*${feature}.php" -print | sort | tail -n 1)"
    if [[ -n "$old_file" ]]; then
      printf '%s %s %s\n' "$driver" "$feature" "$(basename "$old_file")" >> "$old_migration_links_file"
    fi
  done
}

is_feature_enabled() {
  local file="$1"
  local enabled_feature

  for enabled_feature in "${enabled_features[@]}"; do
    if [[ "$file" == "src/Features/$enabled_feature/Entity/"* ]]; then
      return 0
    fi
  done

  return 1
}

apply_feature_scope() {
  restore_entities
  backup_entities

  local file
  for file in "${entity_files[@]}"; do
    if is_feature_enabled "$file"; then
      continue
    fi

    perl -0pi -e 's/^([ \t]*)#\[Entity(\([^\n]*\))?\]/$1\/\/ #[Entity$2]/mg' "$file"
    perl -0pi -e '
      s{
        ^([ \t]*)\#\[
        (OneToOne|OneToMany|ManyToOne|ManyToMany|MorphTo|MorphOne|MorphMany|MorphToMany|MorphedByMany)\b
        .*?
        ^([ \t]*public[^\n;]*;\n)
      }{
        my $block = $&;
        $block =~ s/^([ \t]*)/$1\/\/ /mg;
        $block;
      }xmges
    ' "$file"
  done
}

latest_generated_migration() {
  local driver="$1"

  find "migrations/$driver" -type f -name 'MigrationFrom*.php' -print | sort | tail -n 1
}

rename_generated_migration() {
  local driver="$1"
  local feature="$2"
  local sequence="$3"
  local generated_file="$4"
  local date_part
  local class_name
  local target_file
  local old_class

  date_part="$(date +%Y%m%d)"
  class_name="Migration${date_part}${sequence}${feature}"
  target_file="$(dirname "$generated_file")/$class_name.php"
  old_class="$(basename "$generated_file" .php)"

  perl -0pi -e "s/\\bclass \\Q$old_class\\E\\b/class $class_name/" "$generated_file"
  mv "$generated_file" "$target_file"
  update_markdown_links "$(basename "$generated_file")" "$(basename "$target_file")"
  update_old_feature_links "$driver" "$feature" "$(basename "$target_file")"

  printf '%s' "$target_file"
}

disable_transaction_for_migration() {
  local migration_file="$1"

  if grep -q 'function isTransactional' "$migration_file"; then
    return
  fi

  perl -0pi -e 's/(\{\n)([ \t]+protected function up\(\): void)/$1    protected function isTransactional(): bool\n    {\n        return false;\n    }\n\n$2/' "$migration_file"
}

apply_generated_migration() {
  local driver="$1"
  local migration_file="$2"

  log "Applying $migration_file"
  php_console "$driver" articulate:migrate
}

update_markdown_links() {
  local old_name="$1"
  local new_name="$2"
  local markdown_files=()
  local file

  while IFS= read -r file; do
    markdown_files+=("$file")
  done < <(grep -RIl "$old_name" README.md documentation examples src/Features 2>/dev/null || true)
  if ((${#markdown_files[@]} == 0)); then
    return
  fi

  perl -0pi -e "s/\\Q$old_name\\E/$new_name/g" "${markdown_files[@]}"
}

update_old_feature_links() {
  local driver="$1"
  local feature="$2"
  local new_name="$3"
  local map_driver
  local map_feature
  local old_name

  if [[ -z "$old_migration_links_file" || ! -f "$old_migration_links_file" ]]; then
    return
  fi

  while read -r map_driver map_feature old_name; do
    if [[ "$map_driver" == "$driver" && "$map_feature" == "$feature" ]]; then
      update_markdown_links "$old_name" "$new_name"
    fi
  done < "$old_migration_links_file"
}

run_driver() {
  local driver="$1"
  local sequence_number=1
  local sequence
  local feature
  local generated_file
  local renamed_file

  clear_migration_directory "$driver"
  clear_symfony_cache "$driver"

  case "$driver" in
    mysql)
      reset_mysql_database
      ;;
    pgsql)
      reset_pgsql_database
      ;;
  esac

  php_console "$driver" articulate:init

  enabled_features=()
  for feature in "${FEATURES[@]}"; do
    enabled_features+=("$feature")
    apply_feature_scope

    log "Generating $driver migration for $feature"
    debug_relations
    php_console "$driver" articulate:diff

    generated_file="$(latest_generated_migration "$driver")"
    if [[ -z "$generated_file" ]]; then
      echo "No migration generated for $driver/$feature."
      if ((STRICT)); then
        exit 1
      fi
      continue
    fi

    printf -v sequence '%04d00' "$sequence_number"
    renamed_file="$(rename_generated_migration "$driver" "$feature" "$sequence" "$generated_file")"
    if [[ "$driver" == "pgsql" && "$feature" == "${FEATURES[0]}" ]]; then
      disable_transaction_for_migration "$renamed_file"
    fi
    echo "Generated $renamed_file"

    apply_generated_migration "$driver" "$renamed_file"
    sequence_number=$((sequence_number + 1))
  done
}

main() {
  confirm_destructive_work
  old_migration_links_file="$(mktemp "${TMPDIR:-/tmp}/articulate-migration-links.XXXXXX")"
  collect_entity_files
  backup_entities
  trap 'restore_entities; if [[ -n "$old_migration_links_file" ]]; then rm -f "$old_migration_links_file"; fi' EXIT

  start_containers
  run_driver mysql
  run_driver pgsql

  restore_entities
  log "Generated migrations"
  find migrations/mysql migrations/pgsql -type f -name 'Migration*.php' -print | sort
}

main "$@"
