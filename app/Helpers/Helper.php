<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;



class Helper
{

    //! File or Image Upload
    public static function fileUpload($file, string $folder): ?string
    {
        if (!$file || !$file->isValid()) return null;

        $originalName = $file->getClientOriginalName();
        $name = Str::uuid() . '-' . now()->format('Y-m-d-H-i-s');
        $path = $file->storeAs($folder, $name . '-' . $originalName, ['disk' => 'public']);

        return $path;
    }


    //! File or Image Delete
    public static function fileDelete(?string $path): void
    {
        if (!$path) {
            return; // nothing to delete
        }
        // "public" must match the disk used in fileUpload()
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
