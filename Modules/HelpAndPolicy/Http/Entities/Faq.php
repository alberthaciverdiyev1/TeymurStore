<?php

namespace Modules\HelpAndPolicy\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpAndPolicy\Database\Factories\FaqFactory;

class Faq extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'faqs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'type',
    ];

    public static function newFactory(): FaqFactory
    {
        return FaqFactory::new();
    }

}
