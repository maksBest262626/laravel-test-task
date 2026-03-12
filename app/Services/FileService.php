<?php

namespace App\Services;

use App\Events\UserActionPerformed;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileService
{
    public function upload(User $user, UploadedFile $uploadedFile): File
    {
        $path = $uploadedFile->store('uploads/' . $user->id, 'public');

        $file = File::create([
            'user_id' => $user->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
        ]);

        UserActionPerformed::dispatch($user, 'file_uploaded', 'Uploaded file: ' . $file->original_name);

        return $file;
    }

    public function delete(User $user, File $file): void
    {
        Storage::disk('public')->delete($file->path);

        UserActionPerformed::dispatch($user, 'file_deleted', 'Deleted file: ' . $file->original_name);

        $file->delete();
    }
}