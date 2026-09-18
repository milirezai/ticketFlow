<?php

namespace Tests\Feature;

use App\Events\Activity\TicketAttachment;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketFile;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketFileTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function makeTicket(?User $owner = null, ?User $assigned = null): Ticket
    {
        return Ticket::create([
            'subject' => 'Test subject',
            'user_id' => $owner?->id ?? User::factory()->create()->id,
            'assigned_to' => $assigned?->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
    }

    private function makeFile(Ticket $ticket, int $userId): TicketFile
    {
        return $ticket->files()->create([
            'user_id' => $userId,
            'path' => 'tickets/' . $ticket->id . '/test_doc_1234.pdf',
            'type' => 'pdf',
            'size' => 100,
            'status' => true,
        ]);
    }

    private function pdfFile(): UploadedFile
    {
        return UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
    }

    public function test_owner_can_list_ticket_files(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $this->makeFile($ticket, $owner->id);
        $this->makeFile($ticket, $owner->id);
        Sanctum::actingAs($owner);
        $this->getJson(route('tickets.files.index', $ticket->id))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_assigned_expert_can_list_ticket_files(): void
    {
        $owner = User::factory()->create();
        $expert = User::factory()->create();
        $ticket = $this->makeTicket($owner, $expert);
        $this->makeFile($ticket, $owner->id);
        Sanctum::actingAs($expert);
        $this->getJson(route('tickets.files.index', $ticket->id))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unrelated_user_cannot_list_ticket_files(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $this->makeFile($ticket, $owner->id);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('tickets.files.index', $ticket->id))->assertForbidden();
    }

    public function test_owner_can_upload_files(): void
    {
        Event::fake([TicketAttachment::class]);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.files.store', $ticket->id), [
            'files' => [$this->pdfFile(), UploadedFile::fake()->create('img.jpg', 50, 'image/jpeg')],
        ])->assertStatus(201)->assertJsonCount(2, 'data');
        $this->assertDatabaseCount('ticket_files', 2);
        $this->assertDatabaseHas('ticket_files', ['ticket_id' => $ticket->id, 'user_id' => $owner->id, 'type' => 'pdf']);
        $this->assertCount(2, Storage::disk('local')->files('tickets/' . $ticket->id));
        Event::assertDispatched(TicketAttachment::class);
    }

    public function test_assigned_expert_can_upload_files(): void
    {
        $expert = User::factory()->create();
        $ticket = $this->makeTicket(User::factory()->create(), $expert);
        Sanctum::actingAs($expert);
        $this->postJson(route('tickets.files.store', $ticket->id), ['files' => [$this->pdfFile()]])
            ->assertStatus(201)
            ->assertJsonCount(1, 'data');
        $this->assertDatabaseHas('ticket_files', ['ticket_id' => $ticket->id, 'user_id' => $expert->id]);
    }

    public function test_unrelated_user_cannot_upload_files(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('tickets.files.store', $ticket->id), ['files' => [$this->pdfFile()]])
            ->assertForbidden();
    }

    public function test_upload_requires_files(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.files.store', $ticket->id), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_upload_rejects_unsupported_file_type(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.files.store', $ticket->id), [
            'files' => [UploadedFile::fake()->create('doc.txt', 100, 'text/plain')],
        ])->assertUnprocessable()->assertJsonValidationErrors('files.0');
    }

    public function test_owner_can_view_file(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $file = $this->makeFile($ticket, $owner->id);
        Sanctum::actingAs($owner);
        $this->getJson(route('tickets.files.show', [$ticket->id, $file->id]))
            ->assertOk()
            ->assertJsonPath('data.id', $file->id);
    }

    public function test_unrelated_user_cannot_view_file(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $file = $this->makeFile($ticket, $owner->id);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('tickets.files.show', [$ticket->id, $file->id]))->assertForbidden();
    }

    public function test_cannot_view_file_from_another_ticket(): void
    {
        $owner = User::factory()->create();
        $ticketA = $this->makeTicket($owner);
        $ticketB = $this->makeTicket($owner);
        $file = $this->makeFile($ticketA, $owner->id);
        Sanctum::actingAs($owner);
        $this->getJson(route('tickets.files.show', [$ticketB->id, $file->id]))->assertNotFound();
    }

    public function test_owner_can_download_file(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $file = $this->makeFile($ticket, $owner->id);
        Storage::disk('local')->put($file->path, 'file-contents');
        Sanctum::actingAs($owner);
        $this->get(route('tickets.files.download', [$ticket->id, $file->id]))
            ->assertOk()
            ->assertDownload('test_doc_1234.pdf');
    }

    public function test_unrelated_user_cannot_download_file(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $file = $this->makeFile($ticket, $owner->id);
        Sanctum::actingAs(User::factory()->create());
        $this->get(route('tickets.files.download', [$ticket->id, $file->id]))->assertForbidden();
    }

    public function test_owner_can_delete_file(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $file = $this->makeFile($ticket, $owner->id);
        Storage::disk('local')->put($file->path, 'file-contents');
        Sanctum::actingAs($owner);
        $this->deleteJson(route('tickets.files.destroy', [$ticket->id, $file->id]))->assertNoContent();
        $this->assertDatabaseMissing('ticket_files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_assigned_expert_can_delete_file(): void
    {
        $expert = User::factory()->create();
        $ticket = $this->makeTicket(User::factory()->create(), $expert);
        $file = $this->makeFile($ticket, $expert->id);
        Sanctum::actingAs($expert);
        $this->deleteJson(route('tickets.files.destroy', [$ticket->id, $file->id]))->assertNoContent();
        $this->assertDatabaseMissing('ticket_files', ['id' => $file->id]);
    }

    public function test_unrelated_user_cannot_delete_file(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $file = $this->makeFile($ticket, $owner->id);
        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson(route('tickets.files.destroy', [$ticket->id, $file->id]))->assertForbidden();
    }

    public function test_cannot_delete_file_from_another_ticket(): void
    {
        $owner = User::factory()->create();
        $ticketA = $this->makeTicket($owner);
        $ticketB = $this->makeTicket($owner);
        $file = $this->makeFile($ticketA, $owner->id);
        Sanctum::actingAs($owner);
        $this->deleteJson(route('tickets.files.destroy', [$ticketB->id, $file->id]))->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_files(): void
    {
        $this->getJson(route('tickets.files.index', 1))->assertUnauthorized();
        $this->postJson(route('tickets.files.store', 1), [])->assertUnauthorized();
    }
}
