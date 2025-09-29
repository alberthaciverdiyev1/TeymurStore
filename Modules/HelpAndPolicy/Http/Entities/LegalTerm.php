<?php

namespace Modules\HelpAndPolicy\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpAndPolicy\Database\Factories\LegalTermsFactory;

class LegalTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'legal_terms_policies';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
    ];

    public static function newFactory(): LegalTermsFactory
    {
        return LegalTermsFactory::new();
    }
}
