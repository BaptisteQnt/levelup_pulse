<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameTranslation extends Model
{
    protected $fillable = [
        'game_id','lang','summary','storyline','provider','source_hash'
    ];
}
