docker_compose = docker compose -f docker-compose.yml

php_container = php

up:
	$(docker_compose) up --remove-orphans
upd:
	$(docker_compose) up -d --remove-orphans
down:
	$(docker_compose) down
build:
	docker compose up -d --no-deps --build
bashroot:
	$(docker_compose) exec $(php_container) sh
test:
	$(docker_compose) exec $(php_container) sh -c 'vendor/bin/phpunit'
fix:
	$(docker_compose) exec $(php_container) sh -c 'composer fix'
check:
	$(docker_compose) exec $(php_container) sh -c 'composer check'
setup:
	cp .env.example .env
	echo "UID=`id -u`" >> .env
	cp docker/mysql/.env.example docker/mysql/.env
	$(docker_compose) up -d
	$(docker_compose) exec php sh -c "composer install"
	$(docker_compose) exec php sh -c "php artisan key:generate"
	$(docker_compose) exec php sh -c "php artisan migrate --seed"
	$(docker_compose) exec php sh -c "php artisan jwt:secret"
