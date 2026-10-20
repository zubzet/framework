// Tables with mixed collations break queries across them (#184), e.g. the
// UNION of the permission lookup on every authenticated request. Fresh
// installs create every framework table with the framework collation;
// repair:database-collation converts the framework tables of an existing
// database, with --include-app-tables the application tables and the
// database default as well.
describe('Database collation', () => {
    const repair = (options = '') => cy.exec(`docker exec application php index.php repair:database-collation ${options}`, {
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

    it('converts only the framework tables by default', () => {
        cy.request('/CollationProbe/misalign').its('body').then((state) => {
            // z_test_timestamp_type is an application fixture with the z_ prefix, Doctrine 3 (PHP 8.0) creates it as utf8mb3
            expect(state.misaligned).to.include('z_user_permission');
            expect(state.misaligned).not.to.include('collation_probe');
            expect(state.misalignedAll).to.include.members(['collation_probe', 'z_user_permission']);
            expect(state.permissions).to.match(/Illegal mix of collations/);
        });

        repair().then((result) => {
            expect(result.exitCode).to.eq(0);
            expect(result.stdout).to.include('Converting z_user_permission');
            expect(result.stdout).not.to.include('collation_probe');
        });

        cy.request('/CollationProbe/state').its('body').then((state) => {
            expect(state.misaligned).to.deep.equal([]);
            // model_test_insert has a JSON column, which MariaDB keeps as utf8mb4_bin, so it always shows up here
            expect(state.misalignedAll).to.include('collation_probe');
            expect(state.misalignedAll).not.to.include('z_user_permission');
            expect(state.databaseCollation).to.eq('utf8mb4_general_ci');
            expect(state.permissions).to.eq('ok');
        });
    });

    it('converts the application tables and sets the database default with --include-app-tables', () => {
        repair('--include-app-tables').then((result) => {
            expect(result.exitCode).to.eq(0);
            expect(result.stdout).to.include('Converting collation_probe');
        });

        cy.request('/CollationProbe/state').its('body').then((state) => {
            expect(state.misalignedAll).to.deep.equal([]);
            expect(state.databaseCollation).to.eq('utf8mb4_uca1400_ai_ci');
        });
    });

    it('reports nothing to do once every table matches', () => {
        repair().then((result) => {
            expect(result.exitCode).to.eq(0);
            expect(result.stdout).to.include('Nothing to do');
        });
    });
});
