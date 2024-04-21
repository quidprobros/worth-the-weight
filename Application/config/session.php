<?PHP

return [
    "session.cookie" => "some_kind_of_session",
    "session.driver" => "cookie", //"file",
    "session.files" => realpath("../storage/sessions"),
    "session.path" => "/",
    "session.domain" => null
];
