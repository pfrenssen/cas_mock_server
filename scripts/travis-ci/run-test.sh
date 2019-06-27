#!/bin/bash

# Run either PHPUnit tests or PHP_CodeSniffer tests on Travis CI, depending
# on the passed in parameter.

mysql_to_ramdisk() {
  echo " > Move MySQL datadir to RAM disk."
  sudo service mysql stop
  sudo mv /var/lib/mysql /var/run/tmpfs
  sudo ln -s /var/run/tmpfs /var/lib/mysql
  sudo service mysql start
}

case "$1" in
    Behat)
        mysql_to_ramdisk
        ln -s $MODULE_DIR $DRUPAL_DIR/modules/contrib/cas_mock_server
        cd $MODULE_DIR
        ./vendor/bin/drush @travis si minimal --yes
        ./vendor/bin/drush @travis en cas_mock_server --yes
        ./vendor/bin/drush @travis cset cas.settings login_link_enabled 1 --yes
        ./vendor/bin/drush @travis cset cas.settings login_success_message 'You have been logged in using CAS.' --yes
        ./vendor/bin/drush @travis cset cas.settings user_accounts.auto_register 1 --yes
        ./vendor/bin/drush @travis cset cas.settings user_accounts.email_assignment_strategy 1 --yes
        ./vendor/bin/drush @travis cset cas.settings user_accounts.email_attribute 'email' --yes
        ./vendor/bin/behat
        exit $?
        ;;
    PHP_CodeSniffer)
        cd $MODULE_DIR
        composer install
        ./vendor/bin/phpcs
        exit $?
        ;;
    PHPUnit)
        mysql_to_ramdisk
        ln -s $MODULE_DIR $DRUPAL_DIR/modules/contrib/cas_mock_server
        cd $MODULE_DIR
        ./vendor/bin/phpunit
        exit $?
esac
