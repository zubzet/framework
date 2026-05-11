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

});