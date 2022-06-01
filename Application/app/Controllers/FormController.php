<?PHP

namespace App\Controllers;

use Elegant\Sanitizer\Sanitizer;
use Respect\Validation\Validator;
use flight\Engine;

abstract class FormController
{
    protected $data;

    public function __construct(
        public Engine $app,
        private Validator $validator
    ) {
        $this->data = $this->normalize($this->app->request()->data->getData());
    }

    private function normalize($data)
    {
        return (new Sanitizer($data, $this->filters))->sanitize();
    }

    public function validate(int $elevation = 0)
    {
        switch ($elevation) {
            case 2:
                return $this->validator->assert($this->data);
            case 1:
                return $this->validator->check($this->data);
            default:
                return $this->validator->validate($this->data);
        }
    }
}
