# Vanguard
[![build status](http://gitlab.eservicesgroup.com/esg-systems/vanguard/badges/master/build.svg)](http://gitlab.eservicesgroup.com/esg-systems/vanguard/commits/master) [![coverage report](http://gitlab.eservicesgroup.com/esg-systems/vanguard/badges/master/coverage.svg)](http://gitlab.eservicesgroup.com/esg-systems/vanguard/commits/master)

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