<?php

namespace App\Services\B2B;

use App\Models\B2B\B2BAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class B2BAccountService
{
    public function __construct(
        private readonly B2BCommunicationService $communications,
        private readonly B2BApprovalWorkflowService $approvalWorkflow,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(array $data, Request $request, ?User $user = null): B2BAccount
    {
        $documents = $this->storeDocuments($request, $data['type'] ?? 'corporate');
        unset($data['document_company_reg'], $data['document_tax_certificate'], $data['document_institutional_id']);

        $slug = $this->uniqueSlug($data['name']);

        return DB::transaction(function () use ($data, $documents, $slug, $user) {
            $account = B2BAccount::create([
                ...$data,
                ...$documents,
                'slug' => $slug,
                'status' => 'pending',
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'applied_at' => now()->toIso8601String(),
                    'institutional_type' => $data['type'] ?? 'corporate',
                ]),
            ]);

            if ($user) {
                DB::table('b2b_account_users')->updateOrInsert(
                    ['b2b_account_id' => $account->id, 'user_id' => $user->id],
                    [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => 'owner',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->approvalWorkflow->start($account);
            $this->communications->applicationReceived($account);

            return $account->fresh();
        });
    }

    /**
     * @return array<string, ?string>
     */
    private function storeDocuments(Request $request, string $type): array
    {
        $paths = [
            'document_company_reg' => null,
            'document_tax_certificate' => null,
            'document_institutional_id' => null,
        ];

        foreach (array_keys($paths) as $field) {
            /** @var UploadedFile|null $file */
            $file = $request->file($field);
            if ($file) {
                $paths[$field] = $file->store('b2b-applications/'.Str::slug($type), 'public');
            }
        }

        return $paths;
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $i = 1;

        while (B2BAccount::where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }
}
