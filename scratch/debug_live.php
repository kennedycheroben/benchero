<?php
require_once __DIR__ . '/../app/bootstrap.php';

$_ENV['APP_ENV'] = 'production';
$_ENV['SPORTS_PROVIDER'] = 'real';
$_ENV['FOOTBALL_DATA_API_KEY'] = '';

$s = new \Benchero\Services\Sports\SportsService();
$res = $s->getLiveScores();
var_dump($res);
