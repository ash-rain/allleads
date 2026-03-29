<?php

use App\Jobs\ImportLeadsJob;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('imports leads from a CSV file', function (): void {
    Storage::fake('local');

    $csv = "title,category,phone,email\nTest Business,Café,0888999888,test@café.bg\n";
    $path = 'imports/test.csv';
    Storage::put($path, $csv);

    $user  = User::factory()->create();
    $user->assignRole('admin');
    $batch = ImportBatch::factory()->pending()->create(['created_by' => $user->id]);

    ImportLeadsJob::dispatchSync(
        batchId:     $batch->id,
        filePath:    Storage::path($path),
        mimeType:    'text/csv',
        assignTo:    null,
        tagIds:      [],
        triggeredBy: $user->id,
    );

    expect($batch->fresh()->status)->toBe('completed');
    expect(Lead::where('phone', '0888999888')->exists())->toBeTrue();
});
