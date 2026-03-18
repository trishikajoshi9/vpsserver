<?php

namespace App\Actions\Filemanager;

use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use ZipArchive;

class ZipFilesAction
{
    public function __construct(private Filesystem $filesystem) {}

    public function execute(Request $r)
    {
        $r->validate([
            'filesToZip' => 'required|array',
            'intoPath' => 'required|string',
            'zipName' => 'required|string'
        ]);

        try {
            $zip = new ZipArchive();
            $zipPath = rtrim(storage_path('app/private' . $r->intoPath), '/') . '/' . $r->zipName;
            
            // Adjust based on how actual files are stored.
            // If the panel uses a different disk, we must resolve the absolute path because ZipArchive requires absolute paths.

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($r->filesToZip as $file) {
                    $absoluteFilePath = storage_path('app/private' . $file);
                    
                    if (is_dir($absoluteFilePath)) {
                        $this->addDirectoryToZip($zip, $absoluteFilePath, basename($file));
                    } else if (file_exists($absoluteFilePath)) {
                        $zip->addFile($absoluteFilePath, basename($file));
                    }
                }
                $zip->close();
            } else {
                throw new \Exception('Failed to create zip archive.');
            }

            return response()->json([
                'message' => 'Files zipped successfully',
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dirPath, string $zipDirName)
    {
        if (!is_dir($dirPath)) return;
        
        $zip->addEmptyDir($zipDirName);
        $files = scandir($dirPath);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            
            $filePath = $dirPath . '/' . $file;
            $localPath = $zipDirName . '/' . $file;
            
            if (is_dir($filePath)) {
                $this->addDirectoryToZip($zip, $filePath, $localPath);
            } else {
                $zip->addFile($filePath, $localPath);
            }
        }
    }
}
