<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaArtifact extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'qa_run_id',
        'kind',
        'storage_path',
        'meta_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
        ];
    }

    /** @return BelongsTo<QaRun, $this> */
    public function qaRun(): BelongsTo
    {
        return $this->belongsTo(QaRun::class);
    }
}
