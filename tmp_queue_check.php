<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = $app->make(Illuminate\Database\DatabaseManager::class);

echo "Jobs in queue: " . $db->table('jobs')->count() . PHP_EOL;
echo "Failed jobs: " . $db->table('failed_jobs')->count() . PHP_EOL;
echo "WhatsApp messages last 5:" . PHP_EOL;
$msgs = $db->table('whatsapp_messages')->orderBy('created_at','desc')->limit(5)->get();
foreach ($msgs as $msg) {
    echo $msg->id . ' ' . substr($msg->content, 0, 50) . ' role=' . $msg->role . ' created_at=' . $msg->created_at . PHP_EOL;
}
echo "Leads last 5:" . PHP_EOL;
$leads = $db->table('leads')->orderBy('created_at','desc')->limit(5)->get();
foreach ($leads as $lead) {
    echo $lead->id . ' store=' . $lead->store_id . ' phone=' . $lead->customer_phone . ' name=' . substr($lead->customer_name ?? '', 0, 30) . ' created_at=' . $lead->created_at . PHP_EOL;
}
echo "QUEUE_CONNECTION=" . config('queue.default') . PHP_EOL;
echo "DB_CONNECTION=" . config('database.default') . PHP_EOL;
echo "DB_DATABASE=" . config('database.connections.' . config('database.default') . '.database') . PHP_EOL;
echo "APP_ENV=" . config('app.env') . PHP_EOL;
echo "APP_URL=" . config('app.url') . PHP_EOL;
