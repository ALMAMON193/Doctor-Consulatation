<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;



class Helper
{
    /**
     * File upload
     */
    public static function uploadFile($folderName, $file, $fileName = null, $disk = 'public'): string
    {
        $filePath = 'uploads/'.$folderName.'/'.$fileName;

        if ($disk === 's3') {
            Storage::disk($disk)->put(
                $filePath,
                file_get_contents($file),
                [
                    'visibility' => 'public',
                    'ContentType' => $file->getMimeType(),
                    'ContentDisposition' => 'inline',
                ]
            );
        } else {
            Storage::disk($disk)->putFileAs(
                'uploads/'.$folderName,
                $file,
                $fileName
            );
        }

        return $filePath;
    }

    /**
     *  remove file
     */
    public static function deleteFile($filePath, $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
            return true;
        }
        return false;
    }

    /**
     * generate temp or direct url
     */
    public static function generateTempURL($path, $disk = 'public'): ?string
    {
        if (!$path || !Storage::disk($disk)->exists($path)) {
            return null;
        }

        if (method_exists(Storage::disk($disk), 'temporaryUrl')) {
            return Storage::disk($disk)->temporaryUrl(
                $path,
                now()->addMinutes(30),
            );
        }

        return Storage::disk($disk)->url($path);
    }

}
