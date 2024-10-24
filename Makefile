env:
	@cp .env.example .env

new:
	@composer install
	@php artisan migrate
	@php artisan db:seed

seed_users:
	@php artisan db:seed --class=UserSeeder

upgrade:
	@php composer.phar install
	@php artisan migrate
