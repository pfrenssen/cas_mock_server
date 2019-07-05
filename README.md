[![Build Status](https://travis-ci.org/pfrenssen/cas_mock_server.svg?branch=8.x-1.x)](https://travis-ci.org/pfrenssen/cas_mock_server)

CAS mock server
===============

This module provides a mocked CAS server for testing purposes.


Disclaimer
----------

This is an unofficial branch (`8.x-0.x`) which supports PHP 5.6. is not
currently maintained. However, you can submit patches but they will be reviewed
with low priority. For a better developer experience require `"php": ">=7.1"` in
your project and use the official `8.x-1.x` branch. 

This is purely intended for testing. Under no circumstances should this module
be enabled on a production environment.

Passwords are stored insecurely in plain text, and the test users are stored in
a key value store with an expiration time, meaning that all users will be lost
when the expiration time is reached.

Usage
-----

1. Install the module.
2. Enable the module.
3. Configure the module at Administration > Configuration > People > CAS > CAS
   mock server (`/admin/config/people/cas/mock-server`).
4. Add mock users through the API (see below).
5. Start the server using the API or Drush (see below).
6. Try out the mock server by navigating to `/cas` and logging in using the
   credentials of a mock user.


API
---

### Start the mock server

```
$server_manager = \Drupal::service('cas_mock_server.server_manager');
$server_manager->start();
```

### Stop the mock server

```
$server_manager = \Drupal::service('cas_mock_server.server_manager');
$server_manager->stop();
```

### Check if the server is running

```
$server_manager = \Drupal::service('cas_mock_server.server_manager');
$is_running = $server_manager->isServerActive();
```

### List mock users

```
$user_manager = \Drupal::service('cas_mock_server.user_manager');
$users = $user_manager->getUsers();
```

### Add a mock user

```
$user = [
  // These three attributes are required.
  'username' => 'some_user',
  'email' => 'user@example.com',
  'password' => 'mypass',
  // Add other CAS attributes if wanted.
  'firstname' => 'Erika',
  'lastname' => 'Mustermann',
];
$user_manager = \Drupal::service('cas_mock_server.user_manager');
$users = $user_manager->addUser($user);
```


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

### Start the mock server

```
$ drush cas-mock-server:start
```

### Stop the mock server

```
$ drush cas-mock-server:stop
```

### List mock users

```
$ drush cas-mock-server:user-list
```


Behat integration
-----------------

Add our example Context to your list of contexts in `behat.yml`, and configure
the list of CAS attributes you want to use in your Behat scenarios. This will
allow you to use human readable labels for the CAS attributes.

```
default:
  suites:
    default:
      contexts:
        - Drupal\Tests\cas_mock_server\Context\CasMockServerContext:
            attributes_map:
              firstname: First name
              lastname: Last name
```

In your scenarios you can use the `@casMockServer` tag to automatically start
the server at the beginning of the scenario, and delete it at the end. See
`./tests/features/login.feature` for an example scenario.
