describe('Request', () => {

    before(() => {
        cy.dbSeed();
    });

    it('a path ending in a colon and digits is not the start page', () => {
        cy.request({ url: '/anything:1', failOnStatusCode: false }).then((res) => {
            expect(res.status).to.equal(404);
        });
    });

    // Request::getRouteParameter($key) reads named FastRoute placeholders
    // out of $req->urlParameters. Probe route /abc/{userId}/{postId}/byKey
    // emits all three reads in one JSON response so the present-key and
    // `?? null` fallback branches are pinned by a single request.
    it('getRouteParameter(key) returns the value, or null when missing', () => {
        cy.request('/abc/42/99/byKey').then((res) => {
            expect(res.body).to.deep.equal({
                userId: '42',
                postId: '99',
                missing: null,
            });
        });
    });
});
