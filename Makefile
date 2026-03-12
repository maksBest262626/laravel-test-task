##################
# Variables
##################

DOCKER_COMPOSE = docker compose -f ./docker/docker-compose.yaml
DOCKER_EXEC_APP = docker exec -it ${PROJECT_NAME}_php
DOCKER_EXEC_APP_RUN = docker exec ${PROJECT_NAME}_php
DOCKER_EXEC_DB = docker exec -i ${PROJECT_NAME}_db
COMPOSER = ${DOCKER_EXEC_APP_RUN} composer
ARTISAN = ${DOCKER_EXEC_APP_RUN} php artisan
.PHONY: install up down build build-no-cache restart bash logs ps test

ifeq ("$(wildcard ./docker/.env)","")
$(info docker .env is not exist, trying create it)
$(shell cp ./docker/.env.dist ./docker/.env)
endif

include ./docker/.env

export $(shell sed 's/=.*//' ./docker/.env)

export USER_ID=$(shell id -u)
export GROUP_ID=$(shell id -g)
export COMPOSE_PROJECT_NAME=${PROJECT_NAME}

##################
# Install
##################

install: down build up post-install ## Full project installation
	@echo ""
	@echo "Application is ready at http://localhost:${NGINX_HOST_HTTP_PORT}"

post-install: composer-install env-setup key-generate migrate storage-link ## Post-install routines

storage-link: ## Create storage symlink
	@$(ARTISAN) storage:link --force

env-setup: ## Setup .env for docker
	@cp -n .env.example .env || true
	@sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
	@sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=db/' .env
	@sed -i 's/# DB_PORT=3306/DB_PORT=3306/' .env
	@sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=${MYSQL_DATABASE}/' .env
	@sed -i 's/# DB_USERNAME=root/DB_USERNAME=${MYSQL_USER}/' .env
	@sed -i 's/# DB_PASSWORD=/DB_PASSWORD=${MYSQL_PASSWORD}/' .env
	@sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=file/' .env
	@sed -i 's/CACHE_STORE=database/CACHE_STORE=file/' .env

##################
# Docker
##################

build: ## Build containers
	${DOCKER_COMPOSE} build

build-no-cache: ## Build containers without cache
	${DOCKER_COMPOSE} build --no-cache

up: ## Start containers
	${DOCKER_COMPOSE} up -d

down: ## Stop containers and remove volumes
	${DOCKER_COMPOSE} down -v --remove-orphans

stop: ## Stop containers
	${DOCKER_COMPOSE} stop

restart: stop up ## Restart containers

ps: ## Show running containers
	${DOCKER_COMPOSE} ps

logs: ## Show logs, usage: make logs service=php
	@$(eval service ?= php)
	${DOCKER_COMPOSE} logs -f ${service}

bash: ## Enter PHP container
	${DOCKER_EXEC_APP} bash

##################
# Composer
##################

composer-install: ## Install composer dependencies
	@$(COMPOSER) install --no-interaction

composer-update: ## Update composer dependencies
	@$(COMPOSER) update

composer: ## Run composer command, usage: make composer c='require package/name'
	$(if $(c:=), , $(error Command is not set, example: "make composer c='require package/name'"))
	@$(COMPOSER) $(c)

##################
# Laravel
##################

key-generate: ## Generate application key
	@$(ARTISAN) key:generate

migrate: ## Run migrations
	@$(ARTISAN) migrate --force

migrate-fresh: ## Drop all tables and re-run migrations
	@$(ARTISAN) migrate:fresh

seed: ## Run seeders
	@$(ARTISAN) db:seed

fresh: ## Fresh migration with seeders
	@$(ARTISAN) migrate:fresh --seed

artisan: ## Run artisan command, usage: make artisan c='make:model User'
	$(if $(c:=), , $(error Command is not set, example: "make artisan c='make:model User'"))
	@$(ARTISAN) $(c)

##################
# Testing
##################

test: ## Run tests
	@$(ARTISAN) test