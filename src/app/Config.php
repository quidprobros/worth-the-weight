<?PHP
namespace App;

class Config {
    public static function init() {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Powered-By: Me");
        header("X-Content-Type-Options: NOSNIFF");
    }
}
