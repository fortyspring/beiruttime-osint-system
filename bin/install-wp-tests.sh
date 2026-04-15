#!/bin/bash
# WordPress Test Suite Installation Script
# This script downloads and sets up the WordPress test environment

set -e

WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress}

echo "Installing WordPress Test Suite..."
echo "WP_TESTS_DIR: $WP_TESTS_DIR"
echo "WP_CORE_DIR: $WP_CORE_DIR"

# Create directories
mkdir -p $WP_TESTS_DIR
mkdir -p $WP_CORE_DIR

# Download WordPress test suite
if [ ! -d "$WP_TESTS_DIR/.svn" ]; then
    echo "Downloading WordPress test suite..."
    svn co --quiet https://develop.svn.wordpress.org/tags/latest/tests/phpunit/includes/ $WP_TESTS_DIR/includes
    svn co --quiet https://develop.svn.wordpress.org/tags/latest/tests/phpunit/data/ $WP_TESTS_DIR/data
fi

# Download WordPress core
if [ ! -f "$WP_CORE_DIR/wp-load.php" ]; then
    echo "Downloading WordPress core..."
    svn co --quiet https://develop.svn.wordpress.org/tags/latest/src/ $WP_CORE_DIR
fi

# Create wp-tests-config.php
cat > $WP_TESTS_DIR/wp-tests-config.php << EOF
<?php
/* Path to the WordPress codebase you'd like to test. Add a forward slash in the end. */
define( 'ABSPATH', '$WP_CORE_DIR/' );

/*
 * Path to the theme to test with.
 */
define( 'WP_DEFAULT_THEME', 'default' );

// Test with multisite enabled.
// define( 'WP_TESTS_MULTISITE', true );

// Force known bugs to be run.
// define( 'WP_TESTS_FORCE_KNOWN_BUGS', true );

// Test with WordPress debug mode (default).
define( 'WP_DEBUG', true );

// ** MySQL settings ** //
define( 'DB_NAME', '${DB_NAME:-wordpress_test}' );
define( 'DB_USER', '${DB_USER:-root}' );
define( 'DB_PASSWORD', '${DB_PASSWORD:-}' );
define( 'DB_HOST', '${DB_HOST:-localhost}' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

\$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );
EOF

echo ""
echo "WordPress Test Suite installed successfully!"
echo ""
echo "Next steps:"
echo "1. Create a MySQL database named 'wordpress_test':"
echo "   mysql -u root -e \"CREATE DATABASE wordpress_test;\""
echo ""
echo "2. Run the tests:"
echo "   composer test"
echo ""
echo "Or set environment variables for custom DB credentials:"
echo "   DB_USER=username DB_PASSWORD=password composer test"
