// The framework ends a request by throwing EndOfRequest instead of calling exit.
// Fixtures: app/Controllers/EndprobeController.php and app/Routes/EndOfRequestRoutes.php
//
// What an action decides when its request ends (nothing runs after a reply, a failed guard,
// a catch, a finally, middlewares) is unit tested in tests/unit/php/Routing/RequestLifecycleTest.php.
// Here is what needs the real stack: a view through the web server, the headers of a stopped
// request and the exit code of the console.
describe('End of request', () => {
    before(() => {
        cy.dbSeed();
    });

    context('when an action ends the request', () => {
        it('should run nothing of the action after a rendered view', () => {
            cy.request('/endprobe/render').its('body').should('not.contain', 'after-render');
        });

        it('should run the afterMiddleware of the route after a rendered view', () => {
            cy.request('/end-of-request/render').its('body').should('contain', 'afterware-ran');
        });

        it('should send what was printed and the headers that were set before a stop()', () => {
            cy.request('/endprobe/stop').then((res) => {
                expect(res.status).to.eq(202);
                expect(res.headers['x-endprobe']).to.eq('stopped');
                expect(res.body).to.eq('before-stop');
            });
        });
    });

    context('when the action runs from the console', () => {
        it('should count as a successful run', () => {
            cy.exec('docker exec application php index.php run endprobe rest', {
                failOnNonZeroExit: false,
            }).then(({ exitCode, stdout }) => {
                expect(exitCode).to.eq(0);
                expect(stdout).to.contain('success');
                expect(stdout).not.to.contain('after-success');
            });
        });
    });
});
