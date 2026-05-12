describe('Commands', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Execute Command', () => {
        cy.exec('docker exec application php index.php run core index').then((result) => {
            const output = result.stdout;

            expect(output).to.include('Controller Index');
        });
    });

    it('Execute Command with Parameters', () => {
        cy.exec('docker exec application php index.php run core command test und so').then((result) => {
            const output = JSON.parse(result.stdout);
            expect(output).to.be.an('array').that.includes('test', 'und', 'so');
        });
    });

    // AdvancedController::action_command is reachable from the CLI via the
    // run command. Exercises the same dispatch path as the core/index test
    // above but on a different controller.
    it('Execute AdvancedController action_command via CLI', () => {
        cy.exec('docker exec application php index.php run advanced command').then((result) => {
            expect(result.stdout).to.include('Advanced Command Executed');
        });
    });

    // AdvancedController::action_aliases reroutes internally to
    // CoreController::action_action. Same action over HTTP and CLI to
    // prove the internal reroute behaves identically across dispatch paths.
    it('AdvancedController action_aliases via HTTP', () => {
        cy.request('/advanced/aliases').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('Controller Action');
        });
    });

    it('AdvancedController action_aliases via CLI', () => {
        cy.exec('docker exec application php index.php run advanced aliases').then((result) => {
            expect(result.stdout).to.include('Controller Action');
        });
    });

    // Request::checkPermission("console") — guards a controller to CLI-only
    // dispatch. ConsoleOnlyController calls it from __construct so every
    // action it exposes inherits the gate.
    //
    // Branches in Request::checkPermission for the "console" key:
    //   - isCli()                          → return true   (CLI allowed)
    //   - !isCli + boolResult=false        → executePath error/403 + exit
    //   - !isCli + boolResult=true         → return false  (no exit)
    describe('checkPermission("console")', () => {

        it('CLI dispatch: __construct gate allows the action to run', () => {
            cy.exec('docker exec application php index.php run console run').then((result) => {
                expect(result.stdout).to.include('console-only action ran');
            });
        });

        it('HTTP dispatch: __construct gate produces 403 before the action runs', () => {
            cy.request({
                url: '/Console/run',
                failOnStatusCode: false,
            }).then((res) => {
                expect(res.status).to.eq(403);
                expect(res.body).to.not.include('console-only action ran');
            });
        });

        it('HTTP dispatch with boolResult=true: returns false without exiting', () => {
            // /Core/consoleBool sits on a controller without the __construct
            // gate, so the action keeps running and we observe the return value.
            cy.request('/Core/consoleBool').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.eq('denied');
            });
        });
    });

});