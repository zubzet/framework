// Global helper references exposed by src/Support/Helpers.php — zubzet(),
// model(), request(), response(), config(), user(), db(), view(), and the
// duplicate-definition guard that throws when an app tries to redefine one.

describe('Global references', () => {
    it('throws when an app redefines one of the global helper functions', () => {
        cy.request('/collision.php').then((res) => {
            expect(res.body).to.include("The function 'model' is already defined");
        });
    });

    const refs = [
        { url: '/helper/zubzet',        expect: 'TestValue' },
        { url: '/helper/model',         expect: 'HelperModel called' },
        { url: '/helper/request/test',  expect: 'test' },
        { url: '/helper/response',      expect: '"response": "success"' },
        { url: '/helper/config',        expect: 'TestValue' },
        { url: '/helper/user',          expect: 'not logged in' },
        { url: '/helper/db',            expect: 'database connected' },
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
});
