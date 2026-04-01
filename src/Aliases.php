<?php

    namespace ZubZet\Framework;

    class_alias(ZubZet::class, "z_framework");
    class_alias(Database\Connection::class, "z_db");

    class_alias(Core\Controller::class, "z_controller");
    class_alias(Core\Model::class, "z_model");

    class_alias(Message\RequestResponseHandler::class, "RequestResponseHandler");
    class_alias(Message\Request::class, "Request");
    class_alias(Message\Response::class, "Response");

    class_alias(Form\Upload::class, "z_upload");
    class_alias(Form\Validation\Result::class, "FormResult");
    class_alias(Form\Validation\Field::class, "FormField");

    class_alias(Support\Rest::class, "Rest");
    class_alias(Authentication\User::class, "User");

?>