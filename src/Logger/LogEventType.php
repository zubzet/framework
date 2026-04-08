<?php

    namespace ZubZet\Framework\Logger;

    class LogEventType {

        public static const render = "render";
        public static const accountLoginRateLimited = "accountLoginRateLimited";
        public static const passwordResetRequested = "passwordResetRequested";
        public static const passwordReset = "passwordReset";
        public static const accountUpdated = "accountUpdated";
        public static const userCreated = "userCreated";
        public static const userLoggedIn = "userLoggedIn";
        public static const userLoggedInAnother = "userLoggedInAnother";
        public static const userLoggedOut = "userLoggedOut";
        public static const restError = "restError";

    }