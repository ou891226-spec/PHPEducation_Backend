<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaterialTemplateController extends Controller
{
    public function download(): BinaryFileResponse
    {
        $path = public_path('templates/course_template.xlsx');

        abort_unless(is_file($path), 500, '教材匯入範本不存在');

        $response = response()->download(
            $path,
            'course_template.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );

        $encodedName = rawurlencode('教材匯入範本.xlsx');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="course_template.xlsx"; filename*=UTF-8\'\''.$encodedName,
        );

        return $response;
    }
}
