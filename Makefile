install:
	composer install
	cd assets && npm install

build-assets:
	cd assets && npm run build

lint:
	./vendor/bin/phpcs
	./vendor/bin/phpstan analyse -c phpstan.neon

format:
	./vendor/bin/phpcbf

test:
	./vendor/bin/phpunit
