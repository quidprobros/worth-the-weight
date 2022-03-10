<?PHP

namespace App\Enums;

enum PlanSelection
{
    case GREEN;
    case BLUE;
    case PURPLE;

    public function color(): string
    {
        return match($this) 
            {
                self::GREEN => 'green',
                self::BLUE => 'blue',
                self::PURPLE => 'purple',
            };
    }

}
