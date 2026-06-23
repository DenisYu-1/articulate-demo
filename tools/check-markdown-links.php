<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $file): bool {
            $name = $file->getFilename();

            if ($file->isDir()) {
                return !in_array($name, ['.git', 'vendor', 'var', '.phpunit.cache'], true);
            }

            return str_ends_with($name, '.md');
        }
    )
);

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    $path = $file->getPathname();
    $contents = file_get_contents($path);

    if ($contents === false) {
        $errors[] = "Could not read {$path}";
        continue;
    }

    if (!preg_match_all('/(?<!!)\[[^\]]+\]\(([^)]+)\)/', $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        continue;
    }

    foreach ($matches as $match) {
        $target = trim($match[1][0]);

        if ($target === '' || str_starts_with($target, '#') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $target)) {
            continue;
        }

        $target = preg_replace('/#.*/', '', $target) ?? $target;

        if ($target === '') {
            continue;
        }

        $decoded = rawurldecode($target);
        $candidate = realpath(dirname($path) . DIRECTORY_SEPARATOR . $decoded);

        if ($candidate === false) {
            $line = substr_count(substr($contents, 0, $match[1][1]), "\n") + 1;
            $relativePath = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
            $errors[] = "{$relativePath}:{$line} missing link target {$target}";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Broken markdown links:\n" . implode("\n", $errors) . "\n");
    exit(1);
}

echo "Markdown links OK\n";
