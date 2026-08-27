<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$html = view('docs.platform-handling-guide', [
    'generatedAt' => now()->timezone(config('app.timezone'))->format('F j, Y'),
])->render();

$pdf = Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
$path = base_path('docs/Ledrix-Platform-Handling-Guide.pdf');

if (! is_dir(dirname($path))) {
    mkdir(dirname($path), 0775, true);
}

$pdf->save($path);

echo $path.PHP_EOL.filesize($path).PHP_EOL;
