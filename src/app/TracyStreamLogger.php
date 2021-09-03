<?PHP

namespace App;

use Tracy\Logger;

// https://github.com/nette/tracy/issues/280#issue-298365264
class TracyStreamLogger extends Logger
{
    public function __construct()
    {
        // intentionally do not call parent constructor,
        // because we don't actually need the parameters
    }


    public function log($value, $priority = self::INFO)
    {
        @file_put_contents(
            'php://stderr',
            str_replace("\n", " ", $this->formatMessage($value)) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
