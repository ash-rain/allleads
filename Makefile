.DEFAULT_GOAL := help

# ─── Variables ────────────────────────────────────────────────────────────────
DC      = docker compose
PHP_SVC = php
ART     = $(DC) exec $(PHP_SVC) php artisan

# ─── Help ─────────────────────────────────────────────────────────────────────
.PHONY: help
help: ## Show this help message
	@awk 'BEGIN{FS=":.*##"} /^[a-zA-Z_-]+:.*##/{printf "  \033[36m%-15s\033[0m %s\n",$$1,$$2}' $(MAKEFILE_LIST)

# ─── Docker Lifecycle ─────────────────────────────────────────────────────────
.PHONY: up
up: ## Start all containers (detached)
	$(DC) up -d

.PHONY: down
down: ## Stop and remove containers
	$(DC) down

.PHONY: build
build: ## Rebuild images
	$(DC) build

# ─── First-time setup ─────────────────────────────────────────────────────────
.PHONY: init
init: up ## Bootstrap the project (first run)
	$(DC) exec $(PHP_SVC) composer install
	$(DC) exec $(PHP_SVC) cp .env.example .env || true
	$(ART) key:generate --force
	$(ART) migrate --force
	$(ART) db:seed --force
	$(DC) exec $(PHP_SVC) npm ci
	$(DC) exec $(PHP_SVC) npm run build
	@echo "✅  AllLeads is ready at http://localhost:8080/app"

# ─── Database ─────────────────────────────────────────────────────────────────
.PHONY: migrate
migrate: ## Run pending migrations
	$(ART) migrate --force

.PHONY: seed
seed: ## Run seeders
	$(ART) db:seed --force

.PHONY: fresh
fresh: ## Drop all tables and re-run migrations + seeders
	$(ART) migrate:fresh --seed --force

# ─── Development ──────────────────────────────────────────────────────────────
.PHONY: test
test: ## Run the Pest test suite
	$(DC) exec $(PHP_SVC) ./vendor/bin/pest

.PHONY: lint
lint: ## Run Laravel Pint (auto-fix)
	$(DC) exec $(PHP_SVC) ./vendor/bin/pint

.PHONY: analyse
analyse: ## Run Larastan static analysis
	$(DC) exec $(PHP_SVC) ./vendor/bin/phpstan analyse

.PHONY: shell
shell: ## Open a shell in the PHP container
	$(DC) exec $(PHP_SVC) sh

.PHONY: tinker
tinker: ## Open Laravel Tinker
	$(ART) tinker

.PHONY: artisan
artisan: ## Run an artisan command — usage: make artisan CMD="route:list"
	$(ART) $(CMD)

.PHONY: composer
composer: ## Run a Composer command — usage: make composer CMD="require foo/bar"
	$(DC) exec $(PHP_SVC) composer $(CMD)

.PHONY: npm
npm: ## Run an npm command — usage: make npm CMD="run dev"
	$(DC) exec $(PHP_SVC) npm $(CMD)

# ─── Logs ─────────────────────────────────────────────────────────────────────
.PHONY: logs
logs: ## Tail container logs
	$(DC) logs -f

# ─── Cache ────────────────────────────────────────────────────────────────────
.PHONY: cache-clear
cache-clear: ## Clear all Laravel caches (config, routes, views, filament, etc.)
	$(ART) optimize:clear
