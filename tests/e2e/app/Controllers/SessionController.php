<?php

use ZubZet\Framework\Authentication\APIKey;
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

    public function action_addWithName(Request $req, Response $res): void {
        $user = User::byId(434);
        $session = Session::add($user, name: 'Named session');
        echo(json_encode($this->getSession($session)));
    }


    /**
     * Session 438 is an ordinary login created in the year 2000, so it is
     * expired until the permanent flag revives it.
     */
    public function action_sessionPermanent(Request $req, Response $res): void {
        $session = Session::byId(438);

        $before = $this->getExpiry($session);

        $session->setPermanent(true);
        $session->refresh();

        echo(json_encode([
            'before' => $before,
            'after'  => $this->getExpiry($session),
        ]));
    }


    /**
     * A session records why it was created and where from, both taken at
     * creation time. User 439 has no seeded session, so this one is fresh.
     */
    public function action_sessionOrigin(Request $req, Response $res): void {
        $session = Session::add(User::byId(439), name: 'Named', reason: 'Support request #42');

        echo(json_encode([
            'name'        => $session->name(),
            'reason'      => $session->reason(),
            'device'      => $session->device(),
            'ipCreation'  => $session->ipCreation(),
            'ipLast'      => $session->ipLast(),
            'requestIp'   => $req->ip(),
            'requestAgent'=> $req->userAgent(),
        ]));
    }

    /**
     * Session 439 is seeded with a stale ip_last, which the authenticated
     * request that carries its cookie has to correct.
     */
    public function action_sessionIpLast(Request $req, Response $res): void {
        echo(json_encode([
            'ipLast'    => Session::byId(439)->ipLast(),
            'requestIp' => $req->ip(),
        ]));
    }


    /**
     *
     * @var API Keys
     *
     */

    /**
     * User 430 owns one login, two api keys and one revoked api key. Each
     * class lists its own kind only.
     */
    public function action_apiKeyByUser(Request $req, Response $res): void {
        $user = User::byId(430);

        echo(json_encode([
            'logins'  => $this->sessionIds(Session::byUser($user)),
            'apiKeys' => $this->sessionIds(APIKey::byUser($user)),
        ]));
    }

    /**
     * Session 430 is a login, session 431 an api key. Looking one up through
     * the other class finds nothing.
     */
    public function action_apiKeyById(Request $req, Response $res): void {
        echo(json_encode([
            'sessionOnLogin' => $this->className(Session::byId(430)),
            'sessionOnKey'   => $this->className(Session::byId(431)),
            'apiKeyOnKey'    => $this->className(APIKey::byId(431)),
            'apiKeyOnLogin'  => $this->className(APIKey::byId(430)),
        ]));
    }

    /**
     * A token says nothing about its kind, so the row picks the class. This is
     * shared through CanUseSession, so both classes resolve both kinds - which
     * is what keeps an api key out of the login timeout when it authenticates.
     */
    public function action_apiKeyByToken(Request $req, Response $res): void {
        $login  = '0430a00000000000000000000000000000000000';
        $apiKey = '0430b00000000000000000000000000000000000';

        echo(json_encode([
            'sessionOnLogin' => $this->className(Session::byToken($login)),
            'sessionOnKey'   => $this->className(Session::byToken($apiKey)),
            'apiKeyOnLogin'  => $this->className(APIKey::byToken($login)),
            'apiKeyOnKey'    => $this->className(APIKey::byToken($apiKey)),
        ]));
    }

    public function action_apiKeyAdd(Request $req, Response $res): void {
        $apiKey = APIKey::add(User::byId(437), name: 'Deployment pipeline', reason: 'CI needs read access');

        echo(json_encode(array_merge([
            'class'  => $this->className($apiKey),
            'userId' => (int) $apiKey->userId(),
            'token'  => $apiKey->token(),
            'reason' => $apiKey->reason(),
        ], $this->getNameAndPermanence($apiKey))));
    }

    /**
     * Api key 434 starts out unnamed and expiring. Both are changed through the
     * object and read back from the refreshed object and a second lookup.
     */
    public function action_apiKeyManage(Request $req, Response $res): void {
        $apiKey = APIKey::byId(434);

        $before = $this->getNameAndPermanence($apiKey);

        $apiKey->setName('Renamed key');
        $apiKey->setPermanent(true);
        $apiKey->refresh();

        echo(json_encode([
            'before' => $before,
            'after'  => $this->getNameAndPermanence($apiKey),
            'stored' => $this->getNameAndPermanence(APIKey::byId(434)),
        ]));
    }

    /**
     * Api key 435 was created in the year 2000 without an extension, so only
     * the permanent flag keeps it alive. Dropping the flag expires it again.
     */
    public function action_apiKeyPermanentExpiry(Request $req, Response $res): void {
        $apiKey = APIKey::byId(435);

        $permanent = $this->getExpiry($apiKey);

        $apiKey->setPermanent(false);
        $apiKey->refresh();

        echo(json_encode([
            'permanent' => $permanent,
            'temporary' => $this->getExpiry($apiKey),
        ]));
    }

    /**
     * Api key 437 is seeded with a name; passing null is the way back to an
     * unnamed key.
     */
    public function action_apiKeyClearName(Request $req, Response $res): void {
        $apiKey = APIKey::byId(437);
        $before = $apiKey->name();

        $apiKey->setName(null);

        echo(json_encode([
            'before' => $before,
            'after'  => APIKey::byId(437)->name(),
        ]));
    }


    /**
     * The names of every session of user 436, who is logged in for real by the
     * login-naming test, so cypress can see what loginAs() stored.
     */
    public function action_loginSessionNames(Request $req, Response $res): void {
        echo(json_encode(array_map(
            fn(Session|APIKey $session) => [
                'name'       => $session->name(),
                'device'     => $session->device(),
                'reason'     => $session->reason(),
                'ipCreation' => $session->ipCreation(),
                'ipLast'     => $session->ipLast(),
            ],
            array_values(Session::byUser(User::byId(436))),
        )));
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
     * The session behind the current cookie, so cypress can read back what a
     * login path stored on it.
     */
    public function action_currentSession(Request $req, Response $res): void {
        $user = $req->booter->user;
        $session = $user->isLoggedIn ? Session::byToken($user->getSessionToken()) : null;

        echo(json_encode([
            'found'  => !is_null($session),
            'reason' => $session?->reason(),
            'name'   => $session?->name(),
            'device' => $session?->device(),
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

    private function getSession(Session|APIKey|null $session): array {
        if ($session === null) {
            return ['found' => false];
        }

        return [
            'id'             => $session->id(),
            'token'          => $session->token(),
            'userId'         => (int) $session->userId(),
            'userIdExec'     => (int) $session->userIdExec(),
            'name'           => $session->name(),
            'device'         => $session->device(),
            'reason'         => $session->reason(),
            'isPermanent'    => $session->isPermanent(),
            'extendedSeconds'=> is_null($session->extendedSeconds()) ? null : (int) $session->extendedSeconds(),
            'created'        => $session->created(),
        ];
    }

    private function getNameAndPermanence(Session|APIKey $session): array {
        return [
            'name'        => $session->name(),
            'isPermanent' => $session->isPermanent(),
        ];
    }

    private function getExpiry(Session|APIKey $session): array {
        return [
            'isExpired'   => $session->isExpired(),
            'expiresAt'   => $session->expiresAt(),
            'isPermanent' => $session->isPermanent(),
        ];
    }

    private function sessionIds(array $sessions): array {
        return array_map(fn(Session|APIKey $session) => $session->id(), $sessions);
    }

    private function className(Session|APIKey|null $session): ?string {
        if (is_null($session)) return null;

        return (new \ReflectionClass($session))->getShortName();
    }

}

?>