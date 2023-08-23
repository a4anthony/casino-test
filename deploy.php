<?php
namespace Deployer;

require "recipe/laravel.php";
require "contrib/npm.php";
require "contrib/php-fpm.php";

// Config

set("repository", "git@github.com:kuponify/backend-api.git");
set("php_fpm_version", "8.2");

// add("shared_files", []);
// add("shared_dirs", []);
// add("writable_dirs", []);

set("writable_dirs", ["storage", "bootstrap/cache"]);

// Hosts

host("a4anthony-test")
    ->set("remote_user", "ploi")
    // ->set("hostname", "24.144.120.159")
    ->set("hostname", "157.90.148.253")
    ->setIdentityFile("~/.ssh/id_teq")
    ->set("branch", "main")
    ->set("deploy_path", "~/casinoslot.com");

// Hooks
task("deploy", [
    "deploy:prepare",
    "deploy:vendors",
    "artisan:storage:link",
    "artisan:view:clear",
    "artisan:config:cache",
    "npm:install",
    "npm:run:prod",
    "deploy:publish",
    "php-fpm:restart",
    "queue:restart",
]);

task("npm:run:prod", function () {
    run("cd {{release_path}} && npm run build");
});

task("queue:restart", function () {
    run("cd {{release_path}} && php artisan queue:restart");
});

// task("ziggy:generate", function () {
//     run("cd {{release_path}} && php artisan ziggy:generate");
// });
//
// task("sitemap:generate", function () {
//     run("cd {{release_path}} && php artisan sitemap:generate");
// });
//
// task("inertia-ssr:stop", function () {
//     run("cd {{release_path}} && php artisan queue:restart");
// });

task("scribe:generate", function () {
    run("cd {{release_path}} && php artisan scribe:generate");
});

task("supervisor:restart", function () {
    run("cd {{release_path}} && sudo service supervisor restart");
});

after("deploy:failed", "deploy:unlock");

desc("Restart php-fpm");
task("php-fpm:restart", function () {
    run('echo "" | sudo -S service php8.2-fpm reload');
});
