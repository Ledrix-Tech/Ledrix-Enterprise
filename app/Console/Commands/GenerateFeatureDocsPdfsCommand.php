<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateFeatureDocsPdfsCommand extends Command
{
    protected $signature = 'docs:generate-pdfs';

    protected $description = 'Write Super Admin and Tenant portal feature PDFs into docs/';

    public function handle(): int
    {
        $generatedAt = now()->toFormattedDateString();
        $dir = base_path('docs');
        File::ensureDirectoryExists($dir);

        $guides = [
            'docs.platform-handling-guide' => 'Ledrix-Platform-Handling-Guide.pdf',
            'docs.tenant-portals-guide' => 'Ledrix-Tenant-Portals-Guide.pdf',
        ];

        foreach ($guides as $view => $filename) {
            $path = $dir.DIRECTORY_SEPARATOR.$filename;
            Pdf::loadView($view, ['generatedAt' => $generatedAt])
                ->setPaper('a4')
                ->save($path);
            $this->info('Wrote '.$path);
        }

        return self::SUCCESS;
    }
}
