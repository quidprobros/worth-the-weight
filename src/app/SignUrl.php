<?PHP

namespace App;

use DateTime;
use Spatie\UrlSigner\MD5UrlSigner;
use Tracy\Debugger;

class SignUrl
{
    private $signer;
    private $validTime;

    public function __construct(MD5UrlSigner $signer)
    {
        $this->signer = $signer;
        $this->validTime = (new \DateTime("2021-03-16 04:45:50.784429 UTC"))->modify('-30 seconds');
    }

    public function setDays(int $days): SignUrl
    {
        $this->validTime = $days;
        return this;
    }

    public function sign(string $url): string
    {
        try {
            return $this->signer->sign($url, $this->validTime);
        } catch (\Exception $e) {
            Debugger::log($e->getMessage());
            return "/404";
        }
    }

    public function validate(string $url): bool
    {
        return $this->signer->validate($url);
    }
}
