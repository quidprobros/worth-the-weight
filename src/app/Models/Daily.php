<?PHP

namespace App\Models;

class Daily extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = "id";
    protected $table = "day_records";
    protected $fillable = ["date", "exercised"];
}
