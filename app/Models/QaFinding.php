<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaFinding extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'qa_run_id',
        'rule_key',
        'severity',
        'title',
        'message',
        'recommendation',
        'evidence_json',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evidence_json' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<QaRun, $this> */
    public function qaRun(): BelongsTo
    {
        return $this->belongsTo(QaRun::class);
    }
}
