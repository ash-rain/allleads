<?php

namespace App\Services\Import;

use App\Models\ImportBatch;
use App\Models\Lead;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeadImportPipeline
{
    /**
     * Process a raw array of rows into the database.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  ImportBatch  $batch
     * @param  int|null  $assignTo  User ID to assign imported leads to.
     * @param  array<int>  $tagIds  Tag IDs to attach to imported leads.
     * @return void
     */
    public function process(array $rows, ImportBatch $batch, ?int $assignTo = null, array $tagIds = []): void
    {
        $total = count($rows);
        $batch->update(['total' => $total, 'status' => 'processing', 'progress' => 0]);

        $created = $updated = $skipped = $failed = 0;

        foreach ($rows as $index => $row) {
            try {
                $result = $this->processRow($row, $batch->id, $assignTo, $tagIds);
                match ($result) {
                    'created' => $created++,
                    'updated' => $updated++,
                    'skipped' => $skipped++,
                };
            } catch (\Throwable) {
                $failed++;
            }

            // Update progress every 10 rows to reduce DB writes
            if ($index % 10 === 0 || $index === $total - 1) {
                $batch->update(['progress' => $index + 1]);
            }
        }

        $batch->update([
            'status'        => $failed === $total ? 'failed' : 'completed',
            'progress'      => $total,
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'failed_count'  => $failed,
        ]);
    }

    /** @return 'created'|'updated'|'skipped' */
    private function processRow(array $row, int $batchId, ?int $assignTo, array $tagIds): string
    {
        $data = $this->normalise($row);
        $this->validate($data);

        // Duplicate detection: exact phone match, or title+address similarity
        $existing = $this->findDuplicate($data);

        if ($existing) {
            // Update only if we have more complete data
            $existing->update(array_filter([
                'email'         => $data['email']   ?? $existing->email,
                'website'       => $data['website'] ?? $existing->website,
                'review_rating' => $data['review_rating'] ?? $existing->review_rating,
                'import_batch_id' => $batchId,
            ]));

            if (! empty($tagIds)) {
                $existing->tags()->syncWithoutDetaching($tagIds);
            }

            return 'updated';
        }

        $lead = Lead::create([
            'title'           => $data['title'],
            'category'        => $data['category']        ?? null,
            'address'         => $data['address']         ?? null,
            'phone'           => $data['phone']           ?? null,
            'email'           => $data['email']           ?? null,
            'website'         => $data['website']         ?? null,
            'review_rating'   => $data['review_rating']   ?? null,
            'status'          => Lead::STATUS_NEW,
            'source'          => 'csv',
            'assigned_to'     => $assignTo,
            'import_batch_id' => $batchId,
        ]);

        if (! empty($tagIds)) {
            $lead->tags()->attach($tagIds);
        }

        return 'created';
    }

    /** @return array<string, mixed> */
    private function normalise(array $row): array
    {
        // Map common column name variants to our schema
        $aliases = [
            'name'          => 'title',
            'business_name' => 'title',
            'business'      => 'title',
            'rating'        => 'review_rating',
            'stars'         => 'review_rating',
            'url'           => 'website',
            'site'          => 'website',
            'mail'          => 'email',
        ];

        $normalised = [];
        foreach ($row as $key => $value) {
            $lower = strtolower(str_replace([' ', '-'], '_', $key));
            $mapped = $aliases[$lower] ?? $lower;
            $normalised[$mapped] = $value !== '' ? $value : null;
        }

        return $normalised;
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'title'         => 'required|string|max:255',
            'review_rating' => 'nullable|numeric|min:0|max:5',
            'website'       => 'nullable|url|max:255',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function findDuplicate(array $data): ?Lead
    {
        // 1. Exact phone match
        if (! empty($data['phone'])) {
            $match = Lead::where('phone', $data['phone'])->first();
            if ($match) {
                return $match;
            }
        }

        // 2. Title + address exact match
        if (! empty($data['title']) && ! empty($data['address'])) {
            return Lead::where('title', $data['title'])
                ->where('address', $data['address'])
                ->first();
        }

        return null;
    }
}
