<?php

namespace App\Actions\Filemanager;

use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use ZipArchive;

class ExtractFilesAction
{
    public function __construct(private Filesystem $filesystem) {}

    public function execute(Request $r)
    {
        $r->validate([
            'fileToExtract' => 'required|string',
            'intoPath' => 'required|string',
        ]);

        try {
            $zip = new ZipArchive();
            $zipPath = storage_path('app/private' . $r->fileToExtract);
            $extractPath = rtrim(storage_path('app/private' . $r->intoPath), '/') . '/';

            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                throw new \Exception('Failed to extract zip archive.');
            }

            return response()->json([
                'message' => 'Files extracted successfully',
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
