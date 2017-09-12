<?php
$settings['hash_salt'] = '88CHsp9wdDgSPck';

$settings['trusted_host_patterns'] = array(
  '^drupal-behat\.localhost$',
  '^localhost$',
);
$databases['default']['default'] = array (
  'database' => 'drupal',
  'username' => 'drupal',
  'password' => 'drupal',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
$settings['install_profile'] = 'standard';
$config_directories['sync'] = '../config/sync';
