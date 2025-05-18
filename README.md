horaro - Beautiful Schedules for the Web
========================================

horaro (Esperanto for *schedule*) is a small web application for creating
schedules for stream marathons (e.g. on Twitch or YouTube). It's written in
PHP 8.4 and only requires PHP (duh) and MariaDB 11+ to run.

Features
--------

* Users can register their own accounts (i.e. horaro is meant to provide a
  hosted service for people, although registration can be disabled)
* Schedules can have up to 10 custom columns.
* responsive user interface
* semantic HTML5 markup
* clean, simple URLs
* proper timezone handling (never be confused about no or ambiguous timezone
  stuff in schedules)
* Each schedule is also available as JSON/XML/CSV.
* Each schedule has its own iCal feed for subscribing with Thunderbird or
  Google Calendar (or others).
* Schedules can be themed using Bootswatch.

Requirements
------------

* PHP 8.4+
* MariaDB 11 or compatible MySQL version, with InnoDB support
* a webserver (Apache 2 and nginx are supported, others should work as well)
* mod_rewrite if you use Apache

Download
--------

Clone or download this repository.

You will need to create a dedicated vhost for horaro, because as of now, all
assets and links are absolute. Installing to something like
``http://localhost/horaro/`` will **not work**. Make sure the vhost points to
the ``www`` directory.

Installation
------------

Installation for local development read: [docs/development.md](./docs/development.md)

Installation in a production environment read: [docs/deployment.md](./docs/deployment.md)

### Migrating from v1 (0.7.0)
Read the production deployment instructions :)

License
-------

horaro is licensed under the MIT license.
