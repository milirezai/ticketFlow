<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\TicketFileRequest;
use App\Http\Resources\Api\V1\Ticket\TicketFileResource;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketFile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketFileController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorize('viewAny', $ticket->files()->make());
        return TicketFileResource::collection($ticket->files()->latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketFileRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('create', $ticket->files()->make());
        $files = [];
        foreach ($request->file('files', []) as $file) {
            $name = Str::of($file->getClientOriginalName())->slug() . '_' . time() . '_' . Str::random(4) . '.' . $file->getClientOriginalExtension();
            $files[] = $ticket->files()->create([
                'user_id' => $request->user()->id,
                'path' => $file->storeAs('tickets/' . $ticket->id, $name, 'local'),
                'type' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'status' => true,
            ]);
        }
        return TicketFileResource::collection($files)->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket, TicketFile $file): TicketFileResource
    {
        $this->authorize('view', $file);
        return TicketFileResource::make($file);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket, TicketFile $file)
    {
        $this->authorize('delete', $file);
        $file->delete();
        return response()->noContent();
    }

    public function download(Ticket $ticket, TicketFile $file)
    {
        $this->authorize('view', $file);
        return Storage::disk('local')->download($file->path);
    }
}
