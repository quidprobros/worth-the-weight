<?PHP

namespace App\Listeners;

use App\Events\UserHasRegisteredEvent;

class SendWelcomingEmail
{
    public function handle(UserHasRegisteredEvent $event)
    {
        
    }
}
