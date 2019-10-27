<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'webtesternl_db' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'MJ:c*dQ]Cl_N+0xXc)~|}!MOFwF}d?kzi=6mn[aJv%)hI}U^LRzL&}sJd 6au2.+' );
define( 'SECURE_AUTH_KEY',  'LpVtcBwM|PybT]dWoF44Pi.? EFRrZorhK3vSkh]yXL8P[t%wz(_1Z0w^H]=6f]}' );
define( 'LOGGED_IN_KEY',    'm1{vKJ)0Wk0;PI%:j#;1?TA1LY:ls,]0nXn/;BfIYbtNB&yqc%[mE:D:HUSkDx?N' );
define( 'NONCE_KEY',        '>Y>BLs;]2_42_SPIJ&N&? }&::mhx0Ij<!oKZn6+R?TM-hm {P_m+^bV{s!|]1V ' );
define( 'AUTH_SALT',        '$Ip__--ceUcfV6Xjj?n0SUsDz_<Zjk5I+o0_PBCa|<ZlVe>^{_XYt1=]tNZYp[^9' );
define( 'SECURE_AUTH_SALT', 'fru0/L9+6|l3{z>]LPHoN.-T8s%M{(JIbiYaLhHo_0<*Y4 pOE7>Y`c>r[{U2hH!' );
define( 'LOGGED_IN_SALT',   'kuRY4/>?{.vy<Uk<Z5.K4c0!#JY|[<8z*E9h^|E{fO,[A(-ot6Q>%cyjhB&Y-zn!' );
define( 'NONCE_SALT',       '2#ar<$Qzy8jGw` SO%ac>@$)}iq04Jjv`ZVZ%2/j7J3zzwFCt`?h}/JLtZl</R*R' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
