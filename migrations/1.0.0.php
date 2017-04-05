<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\Account\Model\Permission;
use UserFrosting\Sprinkle\Account\Model\Role;
use UserFrosting\Sprinkle\Account\Model\User;
use UserFrosting\Sprinkle\Core\Model\Verson;

/**
 * Make sure 'account' and/or 'admin' sprinkles aren't in load order.
 * - Inspect version table, ensure 'account' and/or 'admin' migrations not run.
 * - Inspect tables to ensure they don't yet exist.
 */
if (count(Version::whereIn('sprinkle', [ 'account', 'admin' ])->get()) > 0) {
    echo PHP_EOL . "Detected 'account' and/or 'admin' in Version table. This Sprinkle is not compatible with the stock account system in UserFrosting." . PHP_EOL;
    die;
}
$sprinkles = json_decode(file_get_contents(UserFrosting\APP_DIR . '/' . UserFrosting\SPRINKLES_DIR_NAME . '/sprinkles.json'))->base;
if (in_array('account', $sprinkles) || in_array('admin', $sprinkles)) {
    echo PHP_EOL . "Detected 'account' and/or 'admin' in load order. This Sprinkle is not compatible with the stock account system in UserFrosting." . PHP_EOL;
    die;
}
    
/**
 * Users table.
 */
$schema->create('users', function (Blueprint $table) {
    $table->increments('id');
    $table->string('email', 254);
    $table->string('first_name', 30);
    $table->string('last_name', 30);
    $table->string('identity_provider')->comment('The identity provider this user signed up with.');
    $table->string('identity_provider_user_id')->comment('User id with identity provider. Needed as emails can change.');
    $table->string('locale', 10)->default('en_US')->comment('The language and locale to use for this user.');
    $table->boolean('flag_enabled')->default(1)->comment("Set to 1 if the user account is currently enabled, 0 otherwise.  Disabled accounts cannot be logged in to, but they retain all of their data and settings.");
    $table->timestamps();

    $table->unique([ 'identity_provider', 'identity_provider_user_id' ]);
    $table->unique([ 'identity_provider', 'email' ]);
    $table->index('identity_provider_user_id');
    $table->index('email');

    $table->collation = 'utf8_unicode_ci';
    $table->charset = 'utf8mb4';
});
echo "Created table 'users'..." . PHP_EOL;

/**
 * User activity table.
 */
$schema->create('activities', function (Blueprint $table) {
    $table->increments('id');
    $table->string('ip_address', 45)->nullable();
    $table->integer('user_id')->unsigned();
    $table->string('type')->comment('An identifier used to track the type of activity.');
    $table->timestamp('occurred_at');
    $table->text('description')->nullable();

    $table->foreign('user_id')->references('id')->on('users');
    $table->index('user_id');

    $table->collation = 'utf8_unicode_ci';
    $table->charset = 'utf8mb4';
});
echo "Created table 'activities'..." . PHP_EOL;

/**
 * Roles table. Users acquire permissions through roles.
 */
$schema->create('roles', function (Blueprint $table) {
    $table->increments('id');
    $table->string('name');
    $table->string('slug');
    $table->text('description')->nullable();
    $table->timestamps();

    $table->unique('slug');
    $table->index('slug');

    $table->collation = 'utf8_unicode_ci';
    $table->charset = 'utf8mb4';
});

/**
 * RoleUsers table. Creates relation between roles and users.
 */
$schema->create('role_users', function (Blueprint $table) {
    $table->integer('role_id')->unsigned();
    $table->integer('user_id')->unsigned();
    $table->timestamps();

    $table->collation = 'utf8_unicode_ci';
    $table->charset = 'utf8mb4';
});

echo "Created table 'role_users'..." . PHP_EOL;

// Add default roles
$roles = [
    // The root-admin permission is hard coded to the first user for security.
    // Use the authenticators 'isRoot' method to determine if user is root-admin.
    'site-admin' => new Role([
        'slug' => 'site-admin',
        'name' => 'Site Administrator',
        'description' => 'This role is meant for "site administrators", who can basically do anything except create, edit, or delete other administrators.'
    ])
];

foreach ($roles as $slug => $role) {
    $role->save();
}
echo "Created table 'roles'..." . PHP_EOL;

/**
 * Permissions table.
 * Unlike the stock account system, permissions are not evaled as an additional safeguard against exploitation.
 * Changes should also resolve a known issue where evaled code that crashes returns true, not to mention drastically improve debugging experience.
 */
$schema->create('permissions', function(Blueprint $table) {
    $table->increments('id');
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('slug')->comment('A code that references a specific action or URI that an assignee of this permission has access to.');
    $table->text('callback')->comment('A callback used PHP side, that returns True/False to indicate permission.');
    $table->json('parameters')->nullable()->comment('JSON encoded associative array, for which each index is a default value to be passed to callback. Parameter position is specified by index number.');
    $table->timestamps();

    $table->collation = 'utf8_unicode_ci';
    $table->charset = 'utf8mb4';
});

echo "Created table 'permissions'..." . PHP_EOL;

/**
 * Many-to-many mapping between permissions and roles.
 */
$schema->create('permission_roles', function (Blueprint $table) {
    $table->integer('permission_id')->unsigned();
    $table->integer('role_id')->unsigned();
    $table->timestamps();

    $table->primary(['permission_id', 'role_id']);
    $table->foreign('permission_id')->references('id')->on('permissions');
    $table->foreign('role_id')->references('id')->on('roles');
    $table->index('permission_id');
    $table->index('role_id');

    $table->collation = 'utf8_unicode_ci';
    $table->charset = 'utf8mb4';
});

echo "Created table 'permission_roles'..." . PHP_EOL;

$defaultRoleIds = [
    'site-admin' => Role::where('slug', 'site-admin')->first()->id
];

// Add default permissions
$permissions = [];

// Add default mappings to permissions
// Root doesn't need any permissions

/**
 * Many-to-many mapping between roles and users.
 */
$schema->create('role_users', function (Blueprint $table) {
    $table->integer('user_id')->unsigned();
    $table->integer('role_id')->unsigned();
    $table->timestamps();

    $table->primary(['user_id', 'role_id']);
    $table->foreign('user_id')->references('id')->on('users');
    $table->foreign('role_id')->references('id')->on('roles');
    $table->index('user_id');
    $table->index('role_id');

    $table->collation = 'utf8_unicode_ci';
    $table->charset = 'utf8mb4';
});
echo "Created table 'role_users'..." . PHP_EOL;

/**
 * Table for database sessions.
 */
$schema->create('sessions', function (Blueprint $table) {
    $table->string('id')->unique();
    $table->integer('user_id')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->text('payload');
    $table->integer('last_activity');
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users');

    $table->collation = 'utf8_unicode_ci';
    $table->charset = 'utf8mb4';
});
echo "Created table 'sessions'..." . PHP_EOL;

// Make sure that there are no users currently in the user table
// We setup the root account here so it can be done independent of the version check

echo PHP_EOL . 'To complete the installation process, you must set up provide the email and service to be assigned as root admin (master).' . PHP_EOL;
echo 'Please answer the following questions to complete this process:' . PHP_EOL;

// Get service (should output avalible options, and get index response, at some point)
// Get identity providers via services provider, and show list
// if nothing returned, warn and abort.
echo PHP_EOL . 'Please choose the identity provider service: ';
$service = rtrim(fgets(STDIN), "\r\n");
while (strlen($service) < 1 || strlen($service) > 20) {
    echo PHP_EOL . "Invalid service '$service', please try again: ";
    $service = rtrim(fgets(STDIN), "\r\n");
}

// Email
echo PHP_EOL . 'Please provide the email used on the identity provider (1-254 characters, must be compatible with FILTER_VALIDATE_EMAIL): ';
$email = rtrim(fgets(STDIN), "\r\n");
while (strlen($email) < 1 || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo PHP_EOL . "Invalid email '$email', please try again: ";
    $email = rtrim(fgets(STDIN), "\r\n");
}

// Store details in cache
$container->cache->forever('admin_email', $email);
$container->cache->forever('admin_id_provider', $service);

// To make output pretty...
echo PHP_EOL;

echo PHP_EOL . "The 'identity provider' - 'email' combination has been whitelisted. Site is prepared to creation of root-admin upon signing in with this combination." . PHP_EOL;