// Global helper references exposed by src/Support/GlobalReferences.php - model(),
// response() and view() resolved inside a real request.

describe('Global references', () => {
    const refs = [
        { url: '/helper/model',         expect: 'HelperModel called' },
        { url: '/helper/response',      expect: '"response": "success"' },
    ];

    refs.forEach(({ url, expect: needle }) => {
        it(`${url} resolves the helper function`, () => {
            cy.request(url).then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include(needle);
            });
        });
    });

    it('view() renders the named view with the given options', () => {
        cy.request('/helper/view').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('Render');
            expect(res.body).to.include('HelperFunction');
        });
    });

    it('view() resolves a Laravel-style dotted name (core.render == core/render)', () => {
        cy.request('/helper/view_dotted').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('Render');
            expect(res.body).to.include('HelperFunction');
        });
    });
});
