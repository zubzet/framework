// Drives the auth:migrate-hashing console command (HashingAlgorithmMigration):
// it onion-wraps every legacy row, the wrapped rows still authenticate, a real
// login peels a row back to native, and a re-run is a safe no-op. Commands are
// run directly via cy.exec, the same way cy.dbSeed() invokes db:seed.
//
// Both users below start as `legacy` (password "password") per zubzet/1_users.sql.
// User 3 gets logged into (peeled); user 1 is left untouched to prove the re-run
// does not disturb already-onioned rows.

describe('auth:migrate-hashing command', () => {
    const PEELED = { id: 3, email: 'customer@zierhut-it.de' };
    const UNTOUCHED = { id: 1, email: 'admin@zierhut-it.de' };
    const PASSWORD = 'password';

    // XDEBUG_MODE=off keeps stdout clean; cy.exec already fails on a non-zero exit.
    const migrate = () => cy.exec('docker exec -e XDEBUG_MODE=off application php index.php auth:migrate-hashing');
    const scheme = (id) => cy.request(`/AuthProbe/scheme/${id}`).then((r) => JSON.parse(r.body).scheme);
    const authenticates = (id) =>
        cy.request(`/AuthProbe/checkPassword/${id}?password=${PASSWORD}`).then((r) => JSON.parse(r.body).ok);
    const login = (email) =>
        cy.request({
            method: 'POST',
            url: '/login',
            form: true,
            failOnStatusCode: false,
            body: { name: email, password: PASSWORD },
        });

    before(() => cy.dbSeed());

    it('seeds rows as legacy', () => {
        scheme(PEELED.id).then((p) => expect(p).to.eq('legacy'));
        scheme(UNTOUCHED.id).then((p) => expect(p).to.eq('legacy'));
    });

    it('wraps every legacy row to onion, and they still authenticate', () => {
        migrate().then((res) => {
            expect(res.stdout).to.match(/Found \d+ legacy password/);
            expect(res.stdout).to.match(/Done: \d+ row\(s\) wrapped/);
        });
        scheme(PEELED.id).then((p) => expect(p).to.eq('onion'));
        scheme(UNTOUCHED.id).then((p) => expect(p).to.eq('onion'));
        authenticates(PEELED.id).then((ok) => expect(ok).to.eq(true));
        authenticates(UNTOUCHED.id).then((ok) => expect(ok).to.eq(true));
    });

    it('peels an onion row back to native on a real login', () => {
        login(PEELED.email);
        scheme(PEELED.id).then((p) => expect(p).to.eq('native'));
        authenticates(PEELED.id).then((ok) => expect(ok).to.eq(true));
        // A row nobody logged into is unaffected.
        scheme(UNTOUCHED.id).then((p) => expect(p).to.eq('onion'));
    });

    it('is a safe no-op when re-run with no legacy rows left', () => {
        migrate().then((res) => {
            expect(res.stdout).to.match(/No legacy passwords to wrap/);
        });
        scheme(PEELED.id).then((p) => expect(p).to.eq('native'));
        scheme(UNTOUCHED.id).then((p) => expect(p).to.eq('onion'));
        authenticates(PEELED.id).then((ok) => expect(ok).to.eq(true));
        authenticates(UNTOUCHED.id).then((ok) => expect(ok).to.eq(true));
    });

    it('still peels a leftover onion row after the re-run', () => {
        login(UNTOUCHED.email);
        scheme(UNTOUCHED.id).then((p) => expect(p).to.eq('native'));
        authenticates(UNTOUCHED.id).then((ok) => expect(ok).to.eq(true));
    });
});
