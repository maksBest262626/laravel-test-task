<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly FileService $fileService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $files = $request->user()
            ->files()
            ->orderByDesc('created_at')
            ->paginate(20);

        return FileResource::collection($files);
    }

    public function store(UploadFileRequest $request): JsonResponse
    {
        $file = $this->fileService->upload($request->user(), $request->file('file'));

        return (new FileResource($file))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, File $file): JsonResponse
    {
        $this->authorize('delete', $file);

        $this->fileService->delete($request->user(), $file);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}