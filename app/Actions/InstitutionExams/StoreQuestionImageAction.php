<?php

namespace App\Actions\InstitutionExams;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StoreQuestionImageAction
{
    public function execute(?string $image): ?string
    {
        if (empty($image)) {
            return null;
        }

        try {
            $imageData = $image;
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]);
                $imageData = base64_decode($imageData);

                if ($imageData !== false) {
                    $filename = 'question_' . uniqid() . '.' . $type;
                    $path = 'question-images/' . $filename;
                    Storage::disk('public')->put($path, $imageData);

                    return $path;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error uploading question image: ' . $e->getMessage());
        }

        return null;
    }
}
