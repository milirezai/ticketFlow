<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\TicketCategoryRequest;
use App\Http\Resources\Api\V1\Ticket\TicketCategoryResource;
use App\Models\Ticket\TicketCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketCategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(TicketCategory::class, 'ticket_category', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TicketCategoryResource::collection(TicketCategory::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketCategoryRequest $request)
    {
        $category = TicketCategory::create($request->validated());
        return TicketCategoryResource::make($category)->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TicketCategory $ticketCategory)
    {
        return TicketCategoryResource::make($ticketCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketCategoryRequest $request, TicketCategory $ticketCategory)
    {
        $ticketCategory->update($request->validated());
        return TicketCategoryResource::make($ticketCategory->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketCategory $ticketCategory)
    {
        if ($ticketCategory->tickets()->exists()) {
            return response()->json(['message' => 'Cannot delete: tickets reference this record.'], 409);
        }
        $ticketCategory->delete();
        return response()->noContent();
    }
}
