# Horaro production deployment guide

If you are upgrading from horaro 0.x please read the [migration guide](#migrating-from-the-previous-version-070) as well.

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

A full configuration example is available at the bottom of this page.

When running `docker compose up` you will be able to see horaro by visiting `http://localhost/` or by typing the ip of your server.

### Manual (for nginx, apache2 etc)

This manual "guide" assumes the files for horaro are on a server located in `/var/www/horaro`. So the `public` folder would be located in `/var/www/horaro/public`.

You will need to create a dedicated vhost for horaro, because as of now, all
assets and links are absolute. Installing to something like
``http://localhost/horaro/`` will **not work**. Make sure the vhost points to
the ``public`` directory.

First upload the files to where you want to store horaro. Then copy `config/parameters.dist.yml` to `config/parameters.yml` and fill in the configuration items.
For this guide we are assuming that the horaro files are in `/var/www/horaro`, meaning that your config file will need to be placed in `/var/www/horaro/config/parameters.yml`.
Make sure to set the debug option to 'false' and read the [optimus instructions](#about-optimus) to configure ID obscurification.

A full configuration example is available at the bottom of this page.

Create a file named `.env` and enter the following contents into it:
```dotenv
APP_ENV=prod
APP_DEBUG=0
```
This file will be located in `/var/www/horaro/.env`

The next step is to install the requirements that horaro needs, to do that you will need to run the following command.
This command and all others must be run from the horaro "root" directory. This directory is where you copied the files to, for this guide we have always assumed that the location is `/var/www/horaro`.
```shell
composer install --no-dev --no-suggest --prefer-dist --optimize-autoloader
```

The next step is to compile the assets that horaro uses, you can do that via the following command:
```shell
php bin/console asset-map:compile
```

Finally, if you are upgrading your deployment, you may want to clear your cache by running
```shell
php bin/console cache:clear
```

If you want to display the current version on the website, run `git describe --tags --always > version` or manually put something in the version file.


## About Optimus
Horaro uses Optimus to obscure database ids and prevent users from guessing the next sequential id. In order to use optimus you will need to configure it.

Run this command to generate the required numbers: `php vendor/bin/optimus spark`

If the command does not work for whatever reason, read this guide on how to get the numbers anyway: https://github.com/jenssegers/optimus?tab=readme-ov-file#usage


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

### Full configuration example
```yaml
parameters:
  # Your database connection. Set the user, password and dbname. If needed, change
  # the host.
  database.url: "mysql://horaro_user:horaro_pass@127.0.0.1:3306/horaro_dbname?serverVersion=10.11.2-MariaDB&charset=utf8mb4"

  # Your secret.
  # This is a random string of characters. Set it to anything you want, but make it
  # at least 10 characters long and random. You cannot leave this field blank.
  # Something like this is good: http://www.random.org/strings/?num=1&len=20&digits=on&upperalpha=on&loweralpha=on&format=plain
  secret: '!SuperSecretChangeMe!'

  debug: false

  # Read https://github.com/jenssegers/optimus on how to configure these
  optimus:
    prime: ENTER
    inverse: YOUR
    random: OWN

  # Cache times.
  # These determine how long (in minutes) responses of certain kinds (schedules,
  # events, calendar, ...) should be cached on intermediate proxies.
  cache_ttls:
    schedule:  1
    event:    10
    homepage: 10
    calendar: 60
    other:    60

  # The redirect url is https://example.com/-/oauth/callback (replace example with your own domain)
  oauth:
    twitch:
      clientId:     ...
      clientSecret: ...
```
