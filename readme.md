# Vanguard

Vanguard is a project base on Laravel.


## How to install

```
git clone http://gitlab.eservicesgroup.com/esg-systems/vanguard.git
cd vanguard
composer install
cp .env.example .env
```

configure your local database setting in .env file

```
php artisan key:generate
php artisan migrate
```