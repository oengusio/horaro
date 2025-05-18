# Horaro deployment guide

## Migrating from the previous version (0.7.0)
TODO write instructions:
- new parameters file
- converting parameters file


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



## About Optimus
Horaro uses Optimus to obscure database ids and prevent users from guessing the next sequential id. In order to use optimus you will need to configure it.

Command `php vendor/bin/optimus spark`

if command does not work, manual calcuation: https://github.com/jenssegers/optimus?tab=readme-ov-file#usage
