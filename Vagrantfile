# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (we support older styles for
# backwards compatibility). Please don't change it unless you know what
# you're doing.
Vagrant.configure("2") do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://atlas.hashicorp.com/search.
  config.vm.box = "promet/ubuntu-xenial-php7_1-phantomjs"
  config.vm.box_version = "1.2"

  env_prefix  = ENV['DRUPAL_VAGRANT_ENV_PREFIX'] || 'DRUPAL_VAGRANT'
  project     = ENV["#{env_prefix}_PROJECT"] || 'drupal-behat' ## Identify your project, also names your vhost directory
  # End tunables.

  path_drupal         = "/var/www/sites/#{project}.localhost"

  path_drupal_root    = "#{path_drupal}/docroot"

  config.vm.synced_folder ".", "/vagrant", :disabled => true

  config.vm.synced_folder ".", "#{path_drupal}"

  config.vm.hostname = "#{project}.localhost"

  config.ssh.forward_agent = true

  ## mapping guest port 80 to host 8000, websites
  config.vm.network "forwarded_port", guest: 80, host: 8000
  ## mappig guest port 8025 to host port 8025, mailhog interface
  config.vm.network "forwarded_port", guest: 8025, host: 8025


  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  # Example for VirtualBox:
  #
  config.vm.provider "virtualbox" do |vb|
  #   # Display the VirtualBox GUI when booting the machine
  #   vb.gui = true
  #
  #   # Customize the amount of memory on the VM:
    vb.memory = "4096"
    vb.cpus = 2
    vb.customize ["modifyvm", :id, "--audio", "none"]
  end

  config.vm.provision "docker" do |d|
    d.post_install_provision "shell", inline: <<-SHELL
      docker run -d --restart=always -p 8025:8025 -p 1025:1025 -h mailhog.test --name mailhog mailhog/mailhog:latest
    SHELL
  end

  #
  # View the documentation for the provider you are using for more
  # information on available options.

  # Define a Vagrant Push strategy for pushing to Atlas. Other push strategies
  # such as FTP and Heroku are also available. See the documentation at
  # https://docs.vagrantup.com/v2/push/atlas.html for more information.
  # config.push.define "atlas" do |push|
  #   push.app = "YOUR_ATLAS_USERNAME/YOUR_APPLICATION_NAME"
  # end

  # Enable provisioning with a shell script. Additional provisioners such as
  # Puppet, Chef, Ansible, Salt, and Docker are also available. Please see the
  # documentation for more information about their specific syntax and use.
  config.vm.post_up_message = "To complete setup, add the following to your hosts file:

    127.0.0.1 drupal-behat.localhost
    127.0.0.1 mail.localhost

    The URLS to access these projects are:

    http://drupal-behat.localhost:8000
    http://mail.localhost:8025

  "
$shell = <<SHELL
    ## Create hosts file
    echo "Creating hosts file..."
    cp #{path_drupal}/config/local/hosts/hosts /etc/hosts

    ## Create Drush Alias file
    echo "Creating drush aliases..."
    mkdir -p /etc/drush/site-aliases
    cp -r #{path_drupal}/config/drush/aliases.php /etc/drush/site-aliases/default.aliases.drushrc.php

    ## Fix configuration for PHP to use mailhog
    cp -r #{path_drupal}/config/local/php/30-mailhog.ini /etc/php/7.1/apache2/conf.d/30-mailhog.ini

    ## Replace apache vhost file and restart apache
    echo "Creating virtual hosts and restarting apache..."
    cp -r #{path_drupal}/config/drush/mass_virtual.conf /etc/apache2/sites-available/mass_virtual.conf
    service apache2 restart

SHELL
config.vm.provision "server-setup", type: "shell", inline: $shell

$shell = <<SHELL
    echo "Setting git hooks..."
    cp #{path_drupal}/scripts/githooks/pre-commit #{path_drupal}/.git/hooks/pre-commit
SHELL
  config.vm.provision "githook-configuration", type: "shell", run: "always", inline: $shell

$shell = <<SHELL
    ## Creates multisite install directories
    echo "Creating multisite directories..."
    mkdir -p #{path_drupal_root}/sites/#{config.vm.hostname}/files
    cp -R -u -p #{path_drupal}/config/local/settings/files.htaccess #{path_drupal_root}/sites/#{config.vm.hostname}/files/.htaccess

    ## Copies settings files to multisite directories
    echo "Copying settings files..."
    if [ -f "#{path_drupal_root}/sites/#{config.vm.hostname}/settings.php" ]; then chmod a+w #{path_drupal_root}/sites/#{config.vm.hostname}/settings.php; fi
    cp -R -u -f #{path_drupal}/config/local/settings/settings.php #{path_drupal_root}/sites/#{config.vm.hostname}/settings.php
    if [ -f "#{path_drupal_root}/sites/#{config.vm.hostname}/settings.local.php" ]; then chmod a+w #{path_drupal_root}/sites/#{config.vm.hostname}/settings.local.php; fi
    cp -R -u -f #{path_drupal}/config/local/settings/settings.local.php #{path_drupal_root}/sites/#{config.vm.hostname}/settings.local.php
    cp -R -u -f #{path_drupal}/config/local/settings/sites.php #{path_drupal_root}/sites/sites.php
SHELL
  config.vm.provision "site-configuration", type: "shell", run: "always", inline: $shell

$shell = <<SHELL
    ## Create databases
    echo "Creating databases..."
    mysql -u root -e "create database if not exists drupal; grant all on drupal.* to drupal@localhost identified by 'drupal';"
    mysql -u root -e "flush privileges;"

    ## Import seed databases
    echo "Importing seed databases..."
    mysql -u root drupal < #{path_drupal}/config/local/ref_db/default.sql
    drush @default.dev config-set "system.site" name "Drupal-Behat Testing" -y
SHELL
  config.vm.provision "database-setup", type: "shell", inline: $shell

$shell = <<SHELL
    ## Runs drush provisioning steps
    drush @default cim -y
    drush @default csim -y
    drush @default updb -y
    drush @default entup -y
    drush @default cron -y
    drush @default cr -y
SHELL
  config.vm.provision "provision", type: "shell", run: "always", inline: $shell

$shell = <<-SHELL
  /usr/local/bin/phantomjs --webdriver=4444 &> /dev/null &
SHELL
  config.vm.provision "phantomjs", type: "shell", inline: $shell
end


