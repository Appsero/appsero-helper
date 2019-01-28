#!/bin/bash

# Exit if any command fails.
set -e

# Change to the expected directory.
cd "$(dirname "$0")"
cd ..

# Enable nicer messaging for build status.
BLUE_BOLD='\033[1;34m';
GREEN_BOLD='\033[1;32m';
RED_BOLD='\033[1;31m';
YELLOW_BOLD='\033[1;33m';
COLOR_RESET='\033[0m';
error () {
    echo -e "\n${RED_BOLD}$1${COLOR_RESET}\n"
}
status () {
    echo -e "\n${BLUE_BOLD}$1${COLOR_RESET}\n"
}
success () {
    echo -e "\n${GREEN_BOLD}$1${COLOR_RESET}\n"
}
warning () {
    echo -e "\n${YELLOW_BOLD}$1${COLOR_RESET}\n"
}

status "💃 Time to release Appsero Helper 🕺"

# Force composer dump autoload
composer du -o

status "Generating PHP file for wordpress.org to parse translations... 👷‍♂️"
# npx pot-to-php ./languages/appsero-helper.pot ./languages/appsero-helper-translations.php appsero-helper

# Generate the plugin zip file.
status "Creating archive... 🎁"

# If build folder doesn't exist, create one
if [ ! -d build ]; then
    mkdir build
fi

zip -r build/appsero-helper.zip \
    appsero-helper.php \
    includes/*.php \
    includes/**/*.php \
    vendor/* \
    vendor/**/* \
    readme.txt

success "Done. You've built Appsero Helper! 🎉 "
