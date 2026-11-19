// The framework ends a request by throwing EndOfRequest instead of calling exit.
// Fixtures: app/Controllers/EndprobeController.php and app/Routes/EndOfRequestRoutes.php
describe('End of request', () => {
    before(() => {
        cy.dbSeed();
    });

    const body = (path) => cy.request(path).its('body').then((body) => {
        return typeof body === 'string' ? body : JSON.stringify(body);
    });

    context('when an action ends the request', () => {
        it('should run nothing of the action after a rendered view', () => {
            body('/endprobe/render').should('not.contain', 'after-render');
        });

        it('should run nothing of the action after a REST reply', () => {
            body('/endprobe/rest').should('contain', 'success').and('not.contain', 'after-success');
        });

        it('should stop the action behind a failed permission check', () => {
            body('/endprobe/guard').should('not.contain', 'behind-guard');
        });

        it('should not be caught as an exception', () => {
            body('/endprobe/catch').should('not.contain', 'caught').and('not.contain', 'after-catch');
        });

        it('should run finally blocks', () => {
            body('/endprobe/finally').should('contain', 'finally-ran');
        });
    });

    context('when the route has an afterMiddleware', () => {
        it('should run it after a rendered view', () => {
            body('/end-of-request/render').should('contain', 'afterware-ran');
        });

        it('should run it after a REST reply', () => {
            body('/end-of-request/rest').should('contain', 'afterware-ran');
        });

        it('should run it after a failed permission check', () => {
            body('/end-of-request/guard').should('contain', 'afterware-ran').and('not.contain', 'behind-guard');
        });

        it('should not run it behind a blocking middleware', () => {
            body('/end-of-request/blocked').should('contain', 'blocked').and('not.contain', 'afterware-ran');
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
