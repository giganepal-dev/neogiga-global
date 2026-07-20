<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Messaging\Conversation;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\Reseller\ResellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResellerMessagingController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly MessagingService $messaging) {}

    public function index(Request $request, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());

        return $this->success($this->messaging->listFor($reseller, (int) $request->input('limit', 20)));
    }

    public function show(Request $request, int $conversation, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());
        $conv = Conversation::findOrFail($conversation);

        return $this->success([
            'conversation' => $conv,
            'messages' => $this->messaging->messagesFor($conv, $reseller),
        ]);
    }

    public function reply(Request $request, int $conversation, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $conv = Conversation::findOrFail($conversation);

        return $this->success(
            $this->messaging->send($conv, $reseller, $data['body'])
        );
    }

    public function contactAdmin(Request $request, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $admin = User::query()->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))->first()
            ?? User::query()->first();

        abort_if(! $admin, 422, 'No admin contact available.');

        $conversation = $this->messaging->startConversation(
            subject: $data['subject'],
            initiator: $reseller,
            recipient: $admin,
        );
        $message = $this->messaging->send($conversation, $reseller, $data['body']);

        return $this->success(['conversation_id' => $conversation->id, 'message_id' => $message->id], 201);
    }
}
