<?php

namespace ZubZet\Framework\Authentication\Permission;

class Group extends Role {

    public static array $dbExpression = [
        "zr.is_group" => 1
    ];

}