<?php

    namespace ZubZet\Framework\Logger;

    class LogEventType {

        // Framework-emitted events
        public const RENDER = "RENDER";
        public const SLOW_REQUEST = "SLOW_REQUEST";
        public const SLOW_QUERY = "SLOW_QUERY";
        public const EXCEPTION = "EXCEPTION";
        public const ERROR = "ERROR";
        public const WARNING = "WARNING";
        public const NOTICE = "NOTICE";
        public const DEPRECATION = "DEPRECATION";
        public const PARSE = "PARSE";
        public const STRICT = "STRICT";
        public const REST_ERROR = "REST_ERROR";

        // Account / authentication events
        public const ACCOUNT_LOGIN_RATE_LIMITED = "ACCOUNT_LOGIN_RATE_LIMITED";
        public const PASSWORD_RESET_REQUESTED = "PASSWORD_RESET_REQUESTED";
        public const PASSWORD_RESET = "PASSWORD_RESET";
        public const ACCOUNT_UPDATED = "ACCOUNT_UPDATED";
        public const USER_CREATED = "USER_CREATED";
        public const USER_LOGGED_IN = "USER_LOGGED_IN";
        public const USER_LOGGED_IN_ANOTHER = "USER_LOGGED_IN_ANOTHER";
        public const USER_LOGGED_OUT = "USER_LOGGED_OUT";

    }
