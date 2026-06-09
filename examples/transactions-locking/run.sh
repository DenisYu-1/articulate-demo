#!/bin/sh
cd "$(dirname "$0")/../.." && docker compose exec php bin/console app:example:transactions-locking
