<?php

    namespace ZubZet\Framework\Message;

    /**
     * Thrown where the framework used to call exit: the response is complete
     * and nothing of the action may run anymore. Router::execute() catches it
     * at the outermost dispatch, so the request ends by returning.
     *
     * Extends \Error and not \Exception on purpose: a `catch(\Exception)`
     * around a terminating call like `$res->success()` must not swallow it.
     * Never catch it in application code, and rethrow it from a
     * `catch(\Throwable)`.
     */
    final class EndOfRequest extends \Error {
    }

?>
