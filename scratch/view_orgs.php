<?php
$data = json_decode(file_get_contents(__DIR__ . '/audit_results.json'), true);
foreach ($data['organizations'] as $o) {
    echo "ID: " . $o['id'] . "\n";
    echo "  Name: " . $o['name'] . "\n";
    echo "  Slug: " . $o['slug'] . "\n";
    echo "  Status: " . $o['status'] . "\n";
    echo "  Public: " . $o['is_public'] . "\n";
    echo "  Created: " . $o['created_at'] . "\n";
    echo "  Users: " . $o['users_count'] . ", Teams: " . $o['teams_count'] . ", Players: " . $o['players_count'] . "\n";
    echo "--------------------------------------------------\n";
}
