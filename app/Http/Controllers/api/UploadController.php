<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSingleRequest;
use App\Support\RtdPublicUpload;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class UploadController extends Controller
{
    public function single(UploadSingleRequest $request)
    {
        $purpose = $request->input('purpose', 'product');
        $directory = $purpose === 'dispatch'
            ? RtdPublicUpload::DIR_DISPATCH
            : RtdPublicUpload::DIR_PRODUCTS;

        $relativePath = RtdPublicUpload::store($request->file('file'), $directory);

        return Response::success('File uploaded', [
            'path' => $relativePath,
            'url'  => RtdPublicUpload::publicUrl($relativePath),
        ], null, HttpResponse::HTTP_CREATED);
    }
}
