# Drupal Project --- DDEV Setup Guide

This project runs Drupal using **DDEV** for local development.

------------------------------------------------------------------------

## Prerequisites

Install the following on your machine:

-   Docker Desktop
-   DDEV → https://ddev.readthedocs.io
-   Git
-   Composer

Verify installation:

    ddev version
    docker --version
    composer --version

------------------------------------------------------------------------

## First Time Project Setup

### 1. Clone the repository

    git clone <repo-url>
    cd <project-folder>

### 2. Start DDEV

    ddev start

### 3. Install PHP dependencies

    ddev composer install

### 4. Ensure Drupal settings are correct

Open:

    web/sites/default/settings.php

Make sure this exists at the bottom of the file:

``` php
if (file_exists(__DIR__ . '/settings.ddev.php')) {
  include __DIR__ . '/settings.ddev.php';
}
```

This is required for DDEV database connection.

------------------------------------------------------------------------

### 5. Import Database (if dump provided)

    ddev import-db --src=db.sql.gz

### 6. Run database updates

    ddev drush updb -y
    ddev drush cim -y
    ddev drush cr

### 7. Launch the site

    ddev launch

------------------------------------------------------------------------

## Project Structure Rules

### These folders are committed to git

    web/modules/custom
    web/themes/custom
    config/sync
    .ddev
    composer.json

### These are NOT committed (managed by composer/runtime)

    web/core
    web/modules/contrib
    web/themes/contrib
    vendor
    web/sites/*/files

------------------------------------------------------------------------

## Export / Import Database

### Export DB

    ddev export-db | gzip > db.sql.gz

### Import DB

    gunzip < db.sql.gz | ddev import-db

------------------------------------------------------------------------

## Common Commands

  Task                 Command
  -------------------- -----------------
  Start project        `ddev start`
  Stop project         `ddev stop`
  Restart              `ddev restart`
  SSH into container   `ddev ssh`
  Clear cache          `ddev drush cr`
  Check logs           `ddev logs`

------------------------------------------------------------------------

## Important Git Rules

We track:

    web/sites/default/settings.php
    web/sites/default/settings.ddev.php

Do not remove the DDEV include from `settings.php`.

------------------------------------------------------------------------

## If site is not opening

Run:

    ddev poweroff
    ddev start
    ddev logs

Most issues are due to missing `settings.ddev.php` include.

------------------------------------------------------------------------

## For new developers joining

Just run:

    git clone <repo>
    ddev start
    ddev composer install
    ddev launch
