// Drives src/Database/Connection.php through ConnectionProbeController.
// Sections mirror the controller; each describe owns one method.
//
// Coverage target: every reachable line in Connection.php. The single
// branch we cannot reach (assertConnection's heartbeat-fails-then-
// reconnect path on Connection.php:118-122) requires MySQL fault
// injection and is documented as out of scope.

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
