<?PHP

namespace App;

class Payload extends \Aura\Payload\Payload
{

    public function getAll($json_encode = true)
    {
        $arr = [
            "messages" => $this->getMessages(),
            "status" => $this->getStatus(),
            "output" => $this->getOutput(),
        ];

        return $arr;
    }
}
