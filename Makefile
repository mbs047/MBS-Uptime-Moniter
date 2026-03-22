APP_DIR := apps/status
PROBE_DIR := packages/laravel-status-probe

.PHONY: status-install status-dev status-test status-build status-pint status-artisan probe-install probe-test probe-composer

status-install:
	cd $(APP_DIR) && composer install && npm install

status-dev:
	cd $(APP_DIR) && composer run dev

status-test:
	cd $(APP_DIR) && php artisan test

status-build:
	cd $(APP_DIR) && npm run build

status-pint:
	cd $(APP_DIR) && ./vendor/bin/pint --test

status-artisan:
	@test -n "$(CMD)" || (echo 'CMD is required. Use make status-artisan CMD="migrate --seed"' && exit 1)
	cd $(APP_DIR) && php artisan $(CMD)

probe-install:
	cd $(PROBE_DIR) && composer install

probe-test:
	cd $(PROBE_DIR) && composer test

probe-composer:
	@test -n "$(CMD)" || (echo 'CMD is required. Use make probe-composer CMD="update --dry-run"' && exit 1)
	cd $(PROBE_DIR) && composer $(CMD)
