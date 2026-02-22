<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSingleRequest;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function single(UploadSingleRequest $request)
    {
        $storedPath = $request->file('file')->store('uploads/images', 'public');
        $fullUrl = Storage::disk('public')->url($storedPath);

        return Response::success('File uploaded', ['path' => $fullUrl], null, HttpResponse::HTTP_CREATED);
    }
}
