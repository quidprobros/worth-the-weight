<?PHP

namespace App\Models;

class Plans extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "plans";

    public function foods()
    {
        return $this->hasMany(Food::class, "plan_id");
    }
}
