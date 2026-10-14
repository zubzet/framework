// Tables with mixed collations break queries across them (#184), e.g. the
// UNION of the permission lookup on every authenticated request. Fresh
// installs create every framework table with the framework collation;
// repair:database-collation converts an existing database, every table.
describe('Database collation', () => {
    const repair = () => cy.exec('docker exec application php index.php repair:database-collation', {
        failOnNonZeroExit: false,
    });

    // The repair rewrites application tables too, leave a fresh schema behind
    before(() => cy.dbSeed());
    after(() => cy.dbSeed());

    it('creates every framework table with the framework collation', () => {
        const frameworkTables = [
            'z_email_verify', 'z_file', 'z_interaction_log', 'z_interaction_log_category', 'z_language',
            'z_logintoken', 'z_login_too_many_tries', 'z_logintry', 'z_migration_lock', 'z_organization',
            'z_password_reset', 'z_role', 'z_role_permission', 'z_uniqueref', 'z_user', 'z_user_permission',
            'z_user_role', 'z_version',
        ];

        cy.request('/CollationProbe/state').its('body').then((state) => {
            expect(state.collation).to.eq('utf8mb4_uca1400_ai_ci');
            expect(state.misaligned.filter((table) => frameworkTables.includes(table))).to.deep.equal([]);
            expect(state.permissions).to.eq('ok');
        });
    });

    it('converts every table with another collation', () => {
        cy.request('/CollationProbe/misalign').its('body').then((state) => {
            expect(state.misaligned).to.include.members(['collation_probe', 'z_user_permission']);
            expect(state.permissions).to.match(/Illegal mix of collations/);
        });

        repair().then((result) => {
            expect(result.exitCode).to.eq(0);
            expect(result.stdout).to.include('Converting collation_probe');
            expect(result.stdout).to.include('Converting z_user_permission');
        });

        cy.request('/CollationProbe/state').its('body').then((state) => {
            expect(state.misaligned).to.deep.equal([]);
            expect(state.permissions).to.eq('ok');
        });
    });

    it('reports nothing to do once every table matches', () => {
        repair().then((result) => {
            expect(result.exitCode).to.eq(0);
            expect(result.stdout).to.include('Nothing to do');
        });
    });
});
