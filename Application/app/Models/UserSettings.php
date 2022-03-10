<?PHP

namespace App\Models;

class UserSettings extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "users_settings";

    protected $fillable = ["plan_id"];

    public function plan()
    {
        return $this->belongsTo(Plans::class, "plan_id");
    }
}
