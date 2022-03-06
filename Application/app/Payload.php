<?PHP

namespace App;

class Payload extends \Aura\Payload\Payload
{

    public function getAll($json_encode = true)
    {
        $arr = [
            "first_Message" => $this->getMessages()[0],
            "messages" => $this->getMessages(),
            "status" => $this->getStatus(),
            "output" => $this->getOutput(),
        ];

        if (true != $json_encode) {
            return $arr;
        }
        return json_encode($arr);
    }
}
