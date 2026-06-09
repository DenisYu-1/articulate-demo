#!/bin/sh
cd "$(dirname "$0")/../.." && docker compose exec php bin/console articulate:init && docker compose exec php bin/console articulate:diff && docker compose exec php bin/console articulate:migrate
