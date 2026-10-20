<?php

namespace App\Models\Article;

use App\Models\Article\Traits\ArticleAppends;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use ArticleAppends;

    protected $fillable = [
        'name',
        'abstract',
        'relative_path',
    ];
}
