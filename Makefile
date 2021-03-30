COMPOSE=docker-compose -f ./docker-configs/docker-compose.yml

TEST_FILTER=
ifdef filter
	TEST_FILTER = --filter $(filter)
endif

project: laravel-dep up test

laravel-dep:
	cp project/.env.example project/.env
	$(COMPOSE) run --rm composer install

artisan:
	$(COMPOSE) run --rm artisan $(c)

up:
	$(COMPOSE) up -d $(c)

test:
	$(COMPOSE) run --rm tests $(TEST_FILTER)

down:
	$(COMPOSE) down