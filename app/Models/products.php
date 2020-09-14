<?php   namespace App\Models;

use ElasticScoutDriverPlus\CustomSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

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
