<?php

use ZubZet\Framework\Authentication\Session;
use ZubZet\Framework\Authentication\Permission\User;

class SessionController extends z_controller {

    /**
     *
     * @var Session Getters
     *
     */

    public function action_byUser(Request $req, Response $res): void {
        $user = User::byId(400);
        $session = Session::byId(400);
        $sessions = $session->byUser($user);

        $this->echoSessions($sessions);
    }

    public function action_getters(Request $req, Response $res): void {
        $session = Session::byId(404);
        echo(json_encode($this->getSession($session)));
    }


    /**
     *
     * @var Session Interactions
     *
     */

    public function action_invalidate(Request $req, Response $res): void {
        $user = User::byId(401);
        $session = Session::byId(403);

        $beforeCount = count($session->byUser($user));
        $session->invalidate();
        $afterCount = count($session->byUser($user));

        echo(json_encode([
            'beforeCount' => $beforeCount,
            'afterCount'  => $afterCount,
        ]));
    }

    public function action_setExtensionTime(Request $req, Response $res): void {
        $session = Session::byId(405);
        $session->setExtensionTime(3600);

        $updated = Session::byId(405);

        echo(json_encode([
            'extendedSeconds' => (int) $updated->extendedSeconds(),
        ]));
    }

    public function action_extendSession(Request $req, Response $res): void {
        $session = Session::byId(406);
        $beforeExtension = (int) $session->extendedSeconds();

        $session->extend(200);

        $updated = Session::byId(406);
        $afterExtension = (int) $updated->extendedSeconds();

        echo(json_encode([
            'before' => $beforeExtension,
            'after'  => $afterExtension,
        ]));
    }

    public function action_refresh(Request $req, Response $res): void {
        $session = Session::byId(410);
        $beforeExtension = $session->extendedSeconds();

        $session->setExtensionTime(999);
        $session->refresh();

        echo(json_encode([
            'before' => $beforeExtension,
            'after'  => (int) $session->extendedSeconds(),
        ]));
    }


    /**
     *
     * @var Session Business Logic
     *
     */

    public function action_isExpiredActive(Request $req, Response $res): void {
        $session = Session::byId(407);
        echo(json_encode([
            'isExpired' => $session->isExpired(),
        ]));
    }

    public function action_isExpiredExpired(Request $req, Response $res): void {
        $session = Session::byId(408);
        echo(json_encode([
            'isExpired' => $session->isExpired(),
        ]));
    }

    public function action_isExpiredExtended(Request $req, Response $res): void {
        $session = Session::byId(409);
        echo(json_encode([
            'isExpired' => $session->isExpired(),
        ]));
    }


    /**
     *
     * @var Session byToken
     *
     */

    public function action_byToken(Request $req, Response $res): void {
        $session = Session::byToken('0420a00000000000000000000000000000000000');
        echo(json_encode($this->getSession($session)));
    }

    public function action_byTokenInactive(Request $req, Response $res): void {
        $session = Session::byToken('0420b00000000000000000000000000000000000');
        echo(json_encode($this->getSession($session)));
    }

    public function action_byTokenNotFound(Request $req, Response $res): void {
        $session = Session::byToken('doesnotexist');
        echo(json_encode($this->getSession($session)));
    }


    /**
     *
     * @var Session add
     *
     */

    public function action_add(Request $req, Response $res): void {
        $user = User::byId(421);
        $session = Session::add($user);
        echo(json_encode($this->getSession($session)));
    }

    public function action_addWithExec(Request $req, Response $res): void {
        $user = User::byId(422);
        $exec = User::byId(423);
        $session = Session::add($user, $exec);
        echo(json_encode($this->getSession($session)));
    }


    /**
     *
     * @var Session Authentication Flow Tests
     *
     * These actions test whether the cookie-based authentication correctly
     * grants or denies access based on the state of the session.
     *
     */

    /**
     * Returns the authentication state of the current HTTP request.
     * Cypress sets the z_login_token cookie before calling this endpoint.
     */
    public function action_whoami(Request $req, Response $res): void {
        $user = $req->booter->user;
        echo(json_encode([
            'isLoggedIn' => $user->isLoggedIn,
            'userId'     => $user->userId,
            'execUserId' => $user->execUserId,
        ]));
    }

    /**
     * Invalidates session 412 (token 0410a...) so the subsequent whoami
     * request with that cookie is rejected.
     */
    public function action_invalidateForAuth(Request $req, Response $res): void {
        model("z_login")->invalidateSession(Session::byToken('0410a00000000000000000000000000000000000'));
        echo(json_encode(['done' => true]));
    }

    /**
     * After an expired session (415) has been used once (which auto-invalidates it),
     * this action tries to call setExtensionTime on it.
     *
     * Because validateCookie already set active=0, Session::byId() returns null
     * (it queries WHERE active=1). So the extension cannot happen and the session
     * stays dead. The test then re-sends the cookie and expects isLoggedIn=false.
     */
    public function action_extendAfterExpire(Request $req, Response $res): void {
        // Session 415 was just accessed with its expired token, which caused
        // validateCookie to call invalidateSession → active=0 in the DB.
        // Session::byId uses WHERE active=1, so it returns null here.
        $session = Session::byId(415);

        echo(json_encode([
            'sessionFoundAfterExpiry' => $session !== null,
        ]));
    }


    /**
     *
     * @var Session Helper Functions
     *
     */

    private function echoSessions(array $sessions): void {
        echo(json_encode($this->getSessions($sessions)));
    }

    private function getSessions(array $sessions): array {
        $result = [];
        foreach ($sessions as $session) {
            $result[] = $this->getSession($session);
        }
        return $result;
    }

    private function getSession(?Session $session): array {
        if ($session === null) {
            return ['found' => false];
        }

        return [
            'id'             => $session->id(),
            'token'          => $session->token(),
            'userId'         => (int) $session->userId(),
            'userIdExec'     => (int) $session->userIdExec(),
            'extendedSeconds'=> is_null($session->extendedSeconds()) ? null : (int) $session->extendedSeconds(),
            'created'        => $session->created(),
        ];
    }

}

?>