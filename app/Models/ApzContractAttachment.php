<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApzContractAttachment extends Model
{
    public const ROLE_CONTRACT = 'contract';

    public const ROLE_OTHER = 'other';

    protected $table = 'apz_contract_attachments';

    protected $fillable = [
        'contract_id',
        'file_role',
        'stored_path',
        'original_name',
        'mime',
        'size',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ApzContract::class, 'contract_id', 'contract_id');
    }
}
