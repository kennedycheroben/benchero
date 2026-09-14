<?php

$viewsDir = __DIR__ . '/../views';
$dirIterator = new RecursiveDirectoryIterator($viewsDir);
$files = new RecursiveIteratorIterator($dirIterator);

$count = 0;

foreach ($files as $file) 
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Convert href="/o/..." and action="/o/..." to href="<?= url('/o/...') ?>"
    // Convert href="/club/..." and action="/club/..." to href="<?= url('/club/...') ?>"
    $content = preg_replace_callback(
        '/(href|action)="(\/(?:o|club)\/<\?=.*?\?>[^"]*)"/',
        function ($m) {
            $attr = $m[1];
            $val = $m[2];

            $parts = preg_split('/(<\?=.*?\?>)/', $val, -1, PREG_SPLIT_DELIM_CAPTURE);
            $exprs = [];
            foreach ($parts as $p) {
                if ($p === '') continue;
                if (str_starts_with($p, '<?=') && str_ends_with($p, '?>')) {
                    $inside = trim(substr($p, 3, -2));
                    $exprs[] = '(' . $inside . ')';
                } else {
                    $exprs[] = "'" . addslashes($p) . "'";
                }
            }

            $concatenated = implode(' . ', $exprs);
            return $attr . '="<?= url(' . $concatenated . ') ?>"';
        },
        $content
    );

    // Convert href="/admin..." to href="<?= url('/admin...') ?>"
    $content = preg_replace_callback(
        '/(href|action)="(\/admin[^"]*)"/',
        function ($m) {
            $attr = $m[1];
            $path = $m[2];
            return $attr . '="<?= url(' . json_encode($path) . ') ?>"';
        },
        $content
    );

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Updated URLs in: " . basename($filePath) . "\n";
        $count++;
    }


echo "Total view files updated: {$count}\n";
