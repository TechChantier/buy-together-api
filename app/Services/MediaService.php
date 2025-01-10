<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MediaService
{
    /**
     * Uploads a file to the specified storage disk and path.
     */
    public function uploadFile($file, string $directory, ?string $disk = 'public'): array
    {
        try {
            $path = Storage::url($file->store($directory, $disk));

            return [
                'success' => true,
                'message' => 'File uploaded successfully.',
                'path' => $path,
            ];
        } catch (\Exception $e) {
            logger()->error('File upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to upload file.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deletes a file from storage.
     */
    public function deleteFile(string $path, ?string $disk = 'public'): array
    {
        try {
            $deleted = Storage::disk($disk)->delete($path);

            if ($deleted) {
                return [
                    'success' => true,
                    'message' => 'File deleted successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'File not found or could not be deleted.',
            ];
        } catch (\Exception $e) {
            logger()->error('File deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete file.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
