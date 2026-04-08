<?php

    namespace ZubZet\Framework\Logger;

    class LogEventType {

        public const render = "render";
        public const accountLoginRateLimited = "accountLoginRateLimited";
        public const passwordResetRequested = "passwordResetRequested";
        public const passwordReset = "passwordReset";
        public const accountUpdated = "accountUpdated";
        public const userCreated = "userCreated";
        public const userLoggedIn = "userLoggedIn";
        public const userLoggedInAnother = "userLoggedInAnother";
        public const userLoggedOut = "userLoggedOut";
        public const restError = "restError";

    }