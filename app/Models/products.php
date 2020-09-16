<?php   namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use ElasticScoutDriverPlus\CustomSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class products extends Model
{
    use HasFactory, Searchable, CustomSearch;

    protected $table = 'products';

    protected $fillable = [
        'title',
        'price',
        'size',
        'summary',
        'tags',
        'designer',
        'product_id',
        'thumbnail'
    ];


}
