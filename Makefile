.PHONY: up down build fresh migrate seed tinker logs bash db-export db-import test composer-install start stop restart

COMPOSE = docker compose
APP = $(COMPOSE) exec app
BACKUP_FILE ?= ./backups/latest.sql

build:
	$(COMPOSE) build --no-cache

start:
	$(COMPOSE) up -d

stop:
	$(COMPOSE) down

restart:
	$(COMPOSE) down
	$(COMPOSE) up -d

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

fresh:
	$(COMPOSE) down -v
	$(COMPOSE) up -d
	@echo "Waiting for services..."
	@sleep 5
	$(APP) php artisan migrate --force
	$(APP) php artisan db:seed --force

migrate:
	$(APP) php artisan migrate

seed:
	$(APP) php artisan db:seed --force

tinker:
	$(APP) php artisan tinker

logs:
	$(COMPOSE) logs -f

bash:
	$(APP) sh

db-export:
	@mkdir -p ./backups
	$(COMPOSE) exec postgres pg_dump -U better_doodle better_doodle > ./backups/$$(date +%Y-%m-%d).sql
	@echo "Exported to ./backups/$$(date +%Y-%m-%d).sql"

db-import:
	$(COMPOSE) exec -T postgres psql -U better_doodle better_doodle < $(BACKUP_FILE)

test:
	$(APP) php artisan test

composer-install:
	docker run --rm -v $(PWD):/app -w /app composer:2 composer install --no-interaction --prefer-dist --optimize-autoloader
