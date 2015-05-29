<?php namespace Appkr\Fractal\Example;

use Illuminate\Database\Eloquent\Model;

class Manager extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'managers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email'
    ];

    # Relationships

    public function resources() {
        return $this->hasMany(Resource::class);
    }

}
