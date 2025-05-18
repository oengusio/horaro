# Horaro production deployment guide

## Installing in a production environment

### With docker (recommended)
Horaro has a docker image under `oengusio/horaro`. You can choose between the `latest` tag and the name of a github tag.

Example docker compose file:
```yaml
services:
  horaro:
    image: oengusio/horaro:latest
    ports:
        - '80:80' # Don't worry, I also constantly forget the order
    volumes:
      - './horaro_parameters.yml:/var/www/horaro/config/parameters.yml:ro'

```

You can configure horaro by copying `config/parameters.dist.yml` to `horaro_parameters.yml` and following the instructions.
Make sure to set the debug option to 'false' and read the [optimus instructions](#about-optimus) to configure ID obscurification.

### Manual (for nginx, apache2 etc)

You will need to create a dedicated vhost for horaro, because as of now, all
assets and links are absolute. Installing to something like
``http://localhost/horaro/`` will **not work**. Make sure the vhost points to
the ``www`` directory.

First upload the files to where you want to store horaro. Then copy config/parameters.dist.yml to config/parameters.yml and fill in the configuration items.
Make sure to set the debug option to 'false' and read the [optimus instructions](#about-optimus) to configure ID obscurification.

Create a file named `.env` and enter the following contents into it:
```dotenv
APP_ENV=prod
APP_DEBUG=0
```

Run `composer install --no-dev --no-progress --no-suggest --prefer-dist --ignore-platform-reqs --optimize-autoloader`

Run `php bin/console asset-map:compile`

Run `php bin/console cache:clear`

If you want to display the current version on the website, run `git describe --tags --always > version` or manually put something in the version file.


## About Optimus
Horaro uses Optimus to obscure database ids and prevent users from guessing the next sequential id. In order to use optimus you will need to configure it.

Command `php vendor/bin/optimus spark`

if command does not work, manual calcuation: https://github.com/jenssegers/optimus?tab=readme-ov-file#usage


## Migrating from the previous version (0.7.0)
Because of the newly rewritten backend horaro's config file.
To start, parameters.yml now has a top level `parameters` entry.

So if you previously had the following in your file:
```yaml
database:
    driver:   pdo_mysql
    host:     127.0.0.1
    user:     horaro
    password: horaro
    dbname:   horaro
    charset:  utf8
    driverOptions:
      1002: SET NAMES utf8
```

You now will have to put it as:
```yaml
parameters:
    database:
        driver:   pdo_mysql
        host:     127.0.0.1
        user:     horaro
        password: horaro
        dbname:   horaro
        charset:  utf8
        driverOptions:
            1002: SET NAMES utf8
```

etc...

The database object also changed and was collapsed into a single `databse.url` property.

Old configuration:
```yaml
parameters:
    database:
        driver:   pdo_mysql
        host:     127.0.0.1
        user:     horaro_user
        password: horaro_pass
        dbname:   horaro_dbname
        charset:  utf8
        driverOptions:
            1002: SET NAMES utf8
```

New configuration:
```yaml
parameters:
    database.url: 'mysql://horaro_user:horaro_pass@127.0.0.1:3306/horaro_dbname?serverVersion=10.11.2-MariaDB&charset=utf8mb4'
```

A lot of configuration keys also are no longer read and can be removed:
- `hsts_max_age`: It is recommended to have your web-server configure this.
- `cookie_secure`: Symfony will automatically set this if it detects https
- `cookie_lifetime`: Currently not used, but might be made configurable in the future
- `session`: Currently not used, but might be made configurable in the future
- `doctrine_proxies`: Currently not used, but might be made configurable in the future
