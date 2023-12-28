<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    protected $table = 'order_details';
    protected $fillable = ['order_id', 'book_id', 'combo_id', 'price', 'quantity'];
    protected $appends = ['product_name'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }

    public function getProductNameAttribute()
    {
        if ($this->book_id) {
            return $this->book->name;
        } else if ($this->combo_id) {
            return $this->combo->name;
        }

        return 'Không có sản phẩm';
    }
}
