<?PHP

namespace App;

class Payload extends \Aura\Payload\Payload
{

    public function getAll()
    {
        return  [
            "message" => $this->getMessages(),
            "messages" => $this->getMessages(),
            "status" => $this->getStatus(),
            "output" => $this->getOutput(),
        ];
    }
}
