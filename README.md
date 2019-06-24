CAS mock server
===============

This module provides a mocked CAS server for testing purposes.


Disclaimer
----------

This is purely intended for testing. Under no circumstances should this module
be enabled on a production environment.


Usage
-----

1. Install the module.
2. Enable the module.
3. Configure the module at Administration > Configuration > People > CAS > CAS
   mock server (`/admin/config/people/cas/mock-server`).
4. Set up mock users through the API (see below).
5. Start the server using the API or Drush (see below).
6. Try out the mock server by navigating to `/cas` and logging in using the
   credentials of a mock user.


Drush integration
-----------------

The mock server can be controlled from the command line if
[Drush](https://www.drush.org/) is installed.

Since the mock server needs to know the base URL of the Drupal site and this
information is not available when Drupal is invoked from the command line it is
possible that some Drush commands will throw an error message:

> Could not resolve the hostname "default" for the CAS mock server.

To avoid this it is highly recommended to add the base URL to the Drush
configuration. This can be done by adding the following lines to `drush.yml`:

```
options:
  uri: 'http://mysite.local'
```

For more information on how to configure Drush using a `drush.yml` file see
https://github.com/drush-ops/drush/blob/master/examples/example.drush.yml

Alternatively you can pass the base URL of your Drupal site in the `--uri`
when executing Drush commands.

### Starting the mock server

```
$ drush cas-mock-server:start
```

### Stopping the mock server

```
$ drush cas-mock-server:stop
```
