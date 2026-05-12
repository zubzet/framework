describe('Permissions', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Permissions', () => {
        cy.loginAs("admin");
        cy.visit("/Core/Permission");
        cy.contains("Permissions");
        cy.visit("/Core/Permission1");
        cy.contains("Administrator");

        cy.loginAs("customer");
        cy.sendRequest("/Core/Permission", 403);
        cy.visit("/Core/Permission1");
        cy.contains("Administrator").should("not.exist");
    });

    it("should show the correct permissions for the superuser", () => {
        cy.loginAs("admin");
        cy.visit("/admin/e2e-loginas-exec");

        cy.request("/Core/e2e-SuperPermission").its("body")
            .then((body) => {
                const parsed = JSON.parse(body);

                expect(parsed).to.deep.equal({
                    checkSuperPerm: true,
                    checkPerm: false
                });
            });
    });

    // Request::checkPermission's `!user()->isLoggedIn` branch. /Core/permissionCheck
    // runs two checks back-to-back:
    //   1) boolResult=true  — emits "allowed" / "denied" without exiting.
    //   2) default           — !isLoggedIn early-returns into executePath(login)
    //                          + exit (no DB lookup performed), so the trailing
    //                          "core.permissions passed" echo is unreachable.
    describe('checkPermission — !isLoggedIn branch', () => {

        it('without a session: boolResult returns false; the second call short-circuits to login (no "passed")', () => {
            // Belt-and-suspenders: cy.session would already isolate between
            // tests, but explicit clearing makes the "no token" property
            // visible at the assertion site.
            cy.clearCookies();

            cy.request('/Core/permissionCheck').then((res) => {
                expect(res.status).to.eq(200);
                // boolResult call: !isLoggedIn → "denied". No DB lookup happened.
                expect(res.body.startsWith('denied'), 'boolResult call short-circuited').to.be.true;
                // The non-boolResult call would have exited into the login
                // template before the trailing echo could run.
                expect(res.body, 'trailing echo unreachable when not logged in')
                    .to.not.include('core.permissions passed');
            });
        });

        it('with admin session: boolResult returns true; the second call falls through to "passed"', () => {
            cy.loginAs('admin');

            cy.request('/Core/permissionCheck').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body.trim()).to.eq('allowed\ncore.permissions passed');
            });
        });
    });
});