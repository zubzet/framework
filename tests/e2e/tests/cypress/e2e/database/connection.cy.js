// Drives src/Database/Connection.php through ConnectionProbeController.
// Sections mirror the controller; each describe owns one method.
//
// Coverage target: every reachable line in Connection.php. The single
// branch we cannot reach (assertConnection's ping-fails-then-reconnect
// path, where pingConnection() returns false and connect() runs) requires
// MySQL fault injection and is documented as out of scope.

describe('Database/Connection', () => {
    before(() => cy.dbSeed());

    describe('switchUser()', () => {
        it('switches the active MySQL user and restores on the way out', () => {
            cy.request('/ConnectionProbe/switchUser').then((res) => {
                expect(res.body.before).to.match(/^app@/);
                expect(res.body.during).to.match(/^root@/);
                expect(res.body.after).to.match(/^app@/);
            });
        });
    });

    describe('exec() error paths', () => {
        it('throws SQL Error when prepare() rejects the statement', () => {
            cy.request('/ConnectionProbe/execPrepareFail').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.message).to.match(/SQL Error/);
            });
        });

        // PHP 8's mysqli throws ArgumentCountError before bind_param could
        // return false, so the framework's wrapping `if(false===$r)` was
        // dead code (removed). The probe catches whatever surfaces.
        it('surfaces an error when bind_param fails', () => {
            cy.request('/ConnectionProbe/execBindFail').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.type).to.match(/ArgumentCountError|Error|Exception/);
                expect(res.body.message).to.match(/bind variables|type/i);
            });
        });

        it('throws SQL Execution Error when execute() returns false', () => {
            cy.request('/ConnectionProbe/execExecuteFail').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.message).to.match(/SQL Execution Error/);
            });
        });
    });

    describe('heartbeat()', () => {
        it('forced ping returns true against a live connection', () => {
            cy.request('/ConnectionProbe/heartbeatForce').then((res) => {
                expect(res.body.alive).to.eq(true);
            });
        });

        // First call sets lastHeartbeat; second call short-circuits
        // because the previous heartbeat was recent. lastHeartbeat must
        // NOT advance on the short-circuit.
        it('recent-call short-circuits without re-pinging', () => {
            cy.request('/ConnectionProbe/heartbeatRecent').then((res) => {
                expect(res.body.first).to.eq(true);
                expect(res.body.second).to.eq(true);
                expect(res.body.lastHeartbeatStable, 'second call did NOT update lastHeartbeat').to.eq(true);
            });
        });

        // Regression: with lazy-loaded connections a Connection can exist
        // before its socket is opened. heartbeat() must not touch the
        // uninitialized $conn (which fataled with a typed-property Error);
        // it now returns false without throwing. This pins backward
        // compatibility for worker keep-alive loops on future upgrades.
        it('does not fatal before the lazy connection is opened (returns not-alive)', () => {
            cy.request('/ConnectionProbe/heartbeatBeforeConnect').then((res) => {
                expect(res.body.threw, 'heartbeat() must not throw before the connection is opened').to.eq(false);
                expect(res.body.alive).to.eq(false);
            });
        });
    });

    describe('assertConnection()', () => {
        it('returns early when lastConnect is recent (happy path)', () => {
            cy.request('/ConnectionProbe/assertConnectionHappy').then((res) => {
                expect(res.body.value).to.eq(1);
            });
        });

        it('reaches the heartbeat branch when lastConnect is stale', () => {
            cy.request('/ConnectionProbe/assertConnectionViaHeartbeat').then((res) => {
                expect(res.body.value).to.eq(1);
                expect(res.body.heartbeatBumped).to.eq(true);
            });
        });
    });

    describe('execQuery() (Cake Query)', () => {
        it('runs a Cake select with WHERE bindings', () => {
            cy.request('/ConnectionProbe/execQueryWithBindings').then((res) => {
                // z_test_grouping has 2 rows where group_id=1.
                expect(res.body).to.have.lengthOf(2);
            });
        });

        it('runs a Cake select with no bindings (early-return branch)', () => {
            cy.request('/ConnectionProbe/execQueryWithoutBindings').then((res) => {
                // All 3 seeded rows.
                expect(res.body).to.have.lengthOf(3);
            });
        });

        it('maps every supported binding type (integer/float/string)', () => {
            cy.request('/ConnectionProbe/execQueryAllBindingTypes').then((res) => {
                expect(res.body.count).to.eq(1);
            });
        });
    });

    describe('executeMultiQuery()', () => {
        it('returns true on a multi-statement happy path', () => {
            cy.request('/ConnectionProbe/executeMultiQueryHappy').then((res) => {
                expect(res.body.ok).to.eq(true);
            });
        });

        it('returns false when throwOnFailure=false and a statement is malformed', () => {
            cy.request('/ConnectionProbe/executeMultiQueryFailSwallowed').then((res) => {
                expect(res.body.ok).to.eq(false);
            });
        });

        it('throws on malformed input when throwOnFailure=true (default)', () => {
            cy.request('/ConnectionProbe/executeMultiQueryThrows').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.message).to.match(/SQL Multi-Query Error/);
            });
        });
    });

    describe('getDatabaseConnection()', () => {
        it('returns the underlying mysqli handle', () => {
            cy.request('/ConnectionProbe/getDatabaseConnection').then((res) => {
                expect(res.body.isMysqli).to.eq(true);
                expect(res.body.serverInfo).to.be.a('string').and.not.empty;
            });
        });
    });

    // disconnect() is exercised transitively by every connect() call
    // (and therefore by switchUser, the constructor's first query, etc.)
    // so it has no dedicated spec block.

    describe('Constructor', () => {
        it('throws InvalidArgumentException on non-numeric db_connection_timeout', () => {
            cy.request('/ConnectionProbe/constructorNonNumericTimeout').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.type).to.eq('InvalidArgumentException');
                expect(res.body.message).to.match(/db_connection_timeout.*numeric/i);
            });
        });
    });

    // The Galera nodes serve an encrypted transport with a certificate from
    // the suite's own CA, trusted by the application image's system store
    // (Dockerfile.apache-local) the way production trusts a public issuer.
    // These cases prove db_ssl actually changes the transport, and that the
    // certificate is really verified.
    describe('TLS', () => {
        it('connects in plaintext unless configured otherwise', () => {
            cy.request('/ConnectionProbe/tlsTransport').then((res) => {
                expect(res.body.cipher, 'Ssl_cipher').to.eq('');
                expect(res.body.version, 'Ssl_version').to.eq('');
            });
        });

        it('encrypts and verifies with db_ssl = true alone (system trust store)', () => {
            cy.request('/ConnectionProbe/tlsVerified').then((res) => {
                expect(res.body.connected, res.body.error).to.eq(true);
                expect(res.body.cipher, 'Ssl_cipher').to.not.eq('');
                expect(res.body.version, 'Ssl_version').to.match(/^TLSv1\.[23]$/);
            });
        });

        // Without this one the case above would prove nothing: mysqli
        // accepts any certificate when it is given no authority, so an
        // implementation that never resolves one would pass just as happily.
        it('refuses an endpoint the certificate does not name', () => {
            cy.request('/ConnectionProbe/tlsWrongHost').then((res) => {
                expect(res.body.connected, 'connected despite a certificate for another host').to.eq(false);
                expect(res.body.error, 'a verification failure').to.match(/certificate|SSL/i);
            });
        });

        it('rejects a host:port dbhost with a pointer to dbport', () => {
            cy.request('/ConnectionProbe/tlsHostWithPort').then((res) => {
                expect(res.body.connected).to.eq(false);
                expect(res.body.error).to.match(/dbport/);
            });
        });

        // The production setting: the cluster rejects every unencrypted
        // client, so an application that fails to negotiate TLS cannot work.
        describe('against a cluster that requires encrypted transport', () => {
            const NODES = ['galera1', 'galera2', 'galera3'];

            // Applies to new connections only, so requests in flight are
            // unaffected.
            const requireSecureTransport = (value) => {
                NODES.forEach((node) => {
                    cy.exec(
                        `docker exec ${node} mariadb -uroot -proot_password ` +
                        `-e "SET GLOBAL require_secure_transport = ${value}"`,
                        { timeout: 15000 }
                    );
                });
            };

            before(() => {
                cy.saveConfigBackup();
                cy.setConfigSetting('db_ssl', 'true');
                // The pool ignores the transport, so a worker's pooled
                // plaintext connection would survive the db_ssl flip; fresh
                // connections make every request negotiate TLS.
                cy.setConfigSetting('db_persistent', 'false');
                requireSecureTransport('ON');
            });

            after(() => {
                requireSecureTransport('OFF');
                cy.restoreConfigBackup();
            });

            it('serves requests over the encrypted transport', () => {
                cy.request('/ConnectionProbe/tlsTransport').then((res) => {
                    expect(res.body.cipher, 'Ssl_cipher').to.not.eq('');
                    expect(res.body.version, 'Ssl_version').to.match(/^TLSv1\.[23]$/);
                });
            });

            // switchUser() re-opens the connection, so this covers every
            // later (re)connect, including the failover one, carrying TLS.
            it('carries TLS through a reconnect', () => {
                cy.request('/ConnectionProbe/switchUser').then((res) => {
                    expect(res.body.during).to.match(/^root@/);
                    expect(res.body.after).to.match(/^app@/);
                });
            });

            it('runs migrations over the encrypted transport', () => {
                // Migration commands connect through Doctrine DBAL, a
                // separate connection that must carry the same settings.
                // Reporting the lock status at all means that connection was
                // established; without TLS it dies in the driver instead.
                // db:status exits non-zero whenever the lock is open, so the
                // output is what is asserted on, not the exit code.
                cy.exec('docker exec application php index.php db:status', {
                    timeout: 60000,
                    failOnNonZeroExit: false,
                }).then(({ stdout, stderr }) => {
                    expect(stdout, `db:status did not reach the database: ${stderr}`)
                        .to.match(/Migration Lock Status/);
                });
            });
        });
    });

    describe('db_persistent', () => {
        it('reuses the worker connection instead of handshaking again', () => {
            cy.request('/ConnectionProbe/persistentReuse').then((res) => {
                const [first, second] = res.body.persistent;
                expect(second, 'second connection reused the first session').to.eq(first);

                // Same probe without the setting: proof the reuse above comes
                // from db_persistent and not from how the probe connects.
                const [freshFirst, freshSecond] = res.body.fresh;
                expect(freshSecond, 'fresh connections are separate sessions').to.not.eq(freshFirst);
            });
        });
    });

    // Keeps the test-helper at 100% - every other ConnectionProbe action
    // routes a throwing closure through catchThrowableMessage, leaving
    // the no-throw branch unexercised without this probe.
    describe('probe helper', () => {
        it('catchThrowableMessage reports threw=false when the closure succeeds', () => {
            cy.request('/ConnectionProbe/catchHelperHappyPath').then((res) => {
                expect(res.body.threw).to.eq(false);
            });
        });
    });
});
