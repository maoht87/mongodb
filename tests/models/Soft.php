<?php
declare(strict_types=1);

use Omt\Mongodb\Eloquent\Model as Eloquent;
use Omt\Mongodb\Eloquent\SoftDeletes;

/**
 * Class Soft
 * @property \Carbon\Carbon $deleted_at
 */
class Soft extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'soft';
    protected static $unguarded = true;
    protected $dates = ['deleted_at'];
}
