<?PHP

namespace App\Traits;

use Flight;
use Illuminate\Support\Str;
use Illuminate\Events\Dispatcher;

trait UsesUuid
{
    public static function bootUsesUuid(): void
    {
        static::setEventDispatcher( new Dispatcher() );
        static::creating(function ($model) {
            Flight::log($model);
            $model->uuid = Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
