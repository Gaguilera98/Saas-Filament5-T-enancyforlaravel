<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::connection('pool_shared_1');
echo 'pool sessions: '.($db->getSchemaBuilder()->hasTable('sessions') ? 'yes' : 'no')."\n";
echo 'pool password_resets: '.($db->getSchemaBuilder()->hasTable('password_resets') ? 'yes' : 'no')."\n";
