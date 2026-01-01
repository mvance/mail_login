#!/bin/bash
set -e # Exit immediately if a command fails

# 1. Install System Dependencies
echo "Installing system dependencies..."
sudo apt-get update
sudo apt-get install -y php-cli php-curl php-gd php-mbstring php-xml php-sqlite3 unzip git

# 2. Install Composer
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
else
    echo "Composer is already installed."
fi

# 3. Create Drupal Project
if [ -d "drupal_root" ]; then
    echo "Removing existing drupal_root..."
    # Fix permissions before removing, as Drupal makes sites/default read-only
    chmod -R 777 drupal_root/web/sites/default || true
    rm -rf drupal_root
fi

echo "Creating Drupal project in drupal_root..."
composer create-project drupal/recommended-project drupal_root --no-interaction

cd drupal_root

# 4. Install Dependencies
echo "Installing Drush and Dev Dependencies..."
composer require drush/drush drupal/core-dev --dev --no-interaction -W

# 5. Link Module Files (Individually to avoid recursion)
echo "Linking mail_login module files..."
MODULE_DIR="web/modules/contrib/mail_login"
mkdir -p "$MODULE_DIR"

# List of files/directories to link from the repo root
FILES_TO_LINK=(
    "src"
    "tests"
    "config"
    "composer.json"
    "mail_login.config_translation.yml"
    "mail_login.info.yml"
    "mail_login.install"
    "mail_login.links.menu.yml"
    "mail_login.links.task.yml"
    "mail_login.module"
    "mail_login.permissions.yml"
    "mail_login.routing.yml"
    "mail_login.services.yml"
    "README.md"
)

# We are in drupal_root. The repo root is ../
for file in "${FILES_TO_LINK[@]}"; do
    if [ -e "../$file" ]; then
        # The link target needs to be relative to the link location ($MODULE_DIR)
        # MODULE_DIR is web/modules/contrib/mail_login (depth 4 from drupal_root)
        # So we need to go up 4 directories to reach drupal_root, then 1 more to reach repo root.
        # Total: ../../../../../
        ln -s "../../../../../$file" "$MODULE_DIR/$file"
        echo "Linked $file"
    else
        echo "Warning: ../$file not found, skipping."
    fi
done

# 6. Install Drupal
echo "Installing Drupal site..."
./vendor/bin/drush site:install minimal \
  --db-url=sqlite://sites/default/files/.ht.sqlite \
  --site-name="Jules Test Site" \
  --account-name=admin \
  --account-pass=admin \
  -y

# 7. Enable the module
echo "Enabling mail_login module..."
./vendor/bin/drush en mail_login -y

# 8. Fix Permissions
echo "Fixing permissions..."
chmod -R 777 web/sites/default/files

echo "Setup complete!"
