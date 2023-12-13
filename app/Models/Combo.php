<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Combo extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'combos';
    protected $fillable = ['id', 'name', 'supplier_id', 'price', 'quantity', 'description', 'slug', 'image'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function books()
    {
        return $this->belongsToMany(Book::class);
    }
}
