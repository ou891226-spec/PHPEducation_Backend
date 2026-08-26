<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentRosterTemplateController extends Controller
{
    public function download(): BinaryFileResponse
    {
        $path = public_path('templates/student_import_template.xlsx');

        abort_unless(is_file($path), 500, '學生名冊匯入範本不存在');

        $response = response()->download(
            $path,
            'student_import_template.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );

        $encodedName = rawurlencode('學生名冊匯入範本.xlsx');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="student_import_template.xlsx"; filename*=UTF-8\'\''.$encodedName,
        );

        return $response;
    }
}
