// Global helper references exposed by src/Support/Helpers.php - zubzet(),
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

    // The "not yet been setup" branches in zubzet() and db() are only
    // reachable during framework boot - once any request runs, $instance
    // and $z_db are set for that process. instance_test.php hand-walks
    // through those states once and emits a single JSON line covering
    // all four cases.
    describe('boot-state branches (via instance_test.php)', () => {
        let probe;

        before(() => {
            cy.exec('docker exec application php instance_test.php').then((result) => {
                expect(result.exitCode, 'instance_test.php exits cleanly').to.eq(0);
                probe = JSON.parse(result.stdout);
            });
        });

        it('zubzet() throws NotInstantiatedException when ZubZet::$instance is unset', () => {
            expect(probe.zubzetNotInstantiated).to.include('ZubZet (The framework itself)');
            expect(probe.zubzetNotInstantiated).to.include('not yet been setup');
        });

        it('db() rejects non-default connection keys', () => {
            expect(probe.nonDefault).to.eq('Only the default connection is supported so far.');
        });

        it('db("default", allowUnsetConnection: true) returns null when z_db is unset', () => {
            expect(probe.allowedNullWhenUnset).to.be.true;
        });

        it('db() throws NotInstantiatedException("Connection (Database)") when z_db is not a Connection', () => {
            expect(probe.strictWhenUnset).to.include('Connection (Database)');
            expect(probe.strictWhenUnset).to.include('not yet been setup');
        });
    });
});
