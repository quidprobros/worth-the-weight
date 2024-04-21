<?PHP

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGoals extends Model
{
    protected $primaryKey = "id";
    protected $table = "user_goals";

    public function user()
    {
        return $this->hasOne(User::class, 'id');
    }

    public function point_goals()
    {
        return $this->belongsToMany(PointGoals::class);
    }
}
