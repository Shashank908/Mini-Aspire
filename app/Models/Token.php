<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;
    
    protected $table = 'tokens';

	protected $fillable = [ 'access_token', 'user_id', 'refresh_token', 'expires_in' ];

	public function user () {
		return $this->belongsTo(User::class, 'user_id', 'id');
	}
}
