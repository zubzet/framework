// @auth / @guest Blade directives bound by Rendering\Katana\Hooks, exercised
// through RenderDirectiveController + Views/renderprobe/directives. The argument
// is a framework permission (dotted, wildcard-aware), not a Laravel guard, and
// @guest is the negation of @auth.
//
// No cy.dbSeed(): the users/permissions and login tokens come from the base seed
// (support has `dashboard`, lacks `orders.view`; admin has the `*.*` wildcard).
//
// Each marker is unique so a substring check is unambiguous:
//   AUTH / GUEST                      -> @auth / @guest (no argument)
//   AUTH_DASHBOARD / AUTH_ORDERS      -> @auth("dashboard") / @auth("orders.view")
//   GUEST_DASHBOARD / GUEST_ORDERS    -> @guest("dashboard") / @guest("orders.view")

describe('Auth directives (@auth / @guest)', () => {

    const URL = '/RenderDirective/directives';
    const has = (res, token) => expect(res.body, token).to.include(`>${token}<`);
    const lacks = (res, token) => expect(res.body, token).to.not.include(`>${token}<`);

    it('logged out: only the @guest branches render', () => {
        cy.clearCookies();
        cy.request(URL).then((res) => {
            expect(res.status).to.eq(200);
            has(res, 'GUEST');
            has(res, 'GUEST_DASHBOARD'); // guest of a permission the user lacks (not logged in)
            has(res, 'GUEST_ORDERS');
            lacks(res, 'AUTH');
            lacks(res, 'AUTH_DASHBOARD');
            lacks(res, 'AUTH_ORDERS');
        });
    });

    it('logged in without the wildcard: directive argument gates on the permission', () => {
        cy.loginAs('support'); // has `dashboard`, lacks `orders.view`
        cy.request(URL).then((res) => {
            expect(res.status).to.eq(200);
            has(res, 'AUTH');            // logged in
            has(res, 'AUTH_DASHBOARD');  // has dashboard
            has(res, 'GUEST_ORDERS');    // lacks orders.view -> @guest("orders.view") fires
            lacks(res, 'GUEST');            // logged in, so @guest is silent
            lacks(res, 'AUTH_ORDERS');      // lacks orders.view
            lacks(res, 'GUEST_DASHBOARD');  // has dashboard, so @guest("dashboard") is silent
        });
    });

    it('logged in with the *.* wildcard: every @auth(permission) matches', () => {
        cy.loginAs('admin'); // `*.*`
        cy.request(URL).then((res) => {
            expect(res.status).to.eq(200);
            has(res, 'AUTH');
            has(res, 'AUTH_DASHBOARD');
            has(res, 'AUTH_ORDERS'); // wildcard grants orders.view where `support` was denied
            lacks(res, 'GUEST');
            lacks(res, 'GUEST_DASHBOARD');
            lacks(res, 'GUEST_ORDERS');
        });
    });
});
