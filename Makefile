.PHONY: install lint fix test up down rebuild clear-cache ci

install:
	composer install

lint: phpcs php-cs-fixer-dry-run psalm

fix: php-cs-fixer-fix

phpcs:
	./vendor/bin/phpcs

php-cs-fixer-dry-run:
	./vendor/bin/php-cs-fixer fix --dry-run --diff

php-cs-fixer-fix:
	./vendor/bin/php-cs-fixer fix

psalm:
	./vendor/bin/psalm

test:
	./vendor/bin/phpunit

up:
	docker-compose up -d

down:
	docker-compose down

rebuild: down
	docker-compose build --no-cache
	docker-compose up -d

clear-cache:
	rm -rf var/cache/*

ci: lint test

help:
	@echo "Available commands:"
	@echo "  make install     - Install PHP dependencies"
	@echo "  make lint        - Run all linters (PHPCS, PHP-CS-Fixer, Psalm)"
	@echo "  make fix         - Auto-fix code style issues"
	@echo "  make test        - Run PHPUnit tests"
	@echo "  make ci          - Full CI check (lint + test)"
	@echo "  make up          - Start Docker containers"
	@echo "  make down        - Stop Docker containers"
	@echo "  make rebuild     - Rebuild Docker containers"
	@echo "  make clear-cache - Clear application cache"
	@echo "  make help        - Show this help"