// Small migration-system checks bundled together:
//   - Custom DBAL TimeStamp type (src/Database/Migration/Type/TimeStamp.php),
//     exercised by app/Database/migrations/2026-05-08_TimeStampType.php and
//     verified via an INFORMATION_SCHEMA probe.
//   - `db:unlock-migration` command (src/Database/Migration/Commands/UnlockMigration.php).
//   - `db:status` command — asserts the trailing lock status line.
//
// No dbSeed here: the migration schema is already in place from the test
// runner's startup `db:seed`. The describes are ordered so the Unlock
// section ends with the migration table in the unlocked state, which is
// the precondition Status relies on.

describe('Migration System', () => {

    describe('Custom TimeStamp type', () => {
        // import/sync specs reset the migration set, which drops the
        // z_test_timestamp_type table — reseed so this section runs
        // against a fresh schema regardless of spec order.
        before(() => cy.dbSeed());

        it("creates a TIMESTAMP column on the seeded test table", () => {
            cy.request("/migration/checkTimestampType").then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body.found, "z_test_timestamp_type.created exists").to.eq(true);
                expect(res.body.dataType.toLowerCase()).to.eq("timestamp");
                expect(res.body.columnType.toLowerCase()).to.eq("timestamp");
            });
        });
    });

    describe('Unlock', () => {
        const isLocked = () =>
            cy.request('/migration/isMigrationLocked').then((res) => JSON.parse(res.body).locked);

        // Locks via probe, then runs the unlock command. Runs first so the
        // describe leaves the table in the unlocked state.
        it('unlocks the migration table when locked', () => {
            cy.request('/migration/lockMigration').then((res) => {
                expect(JSON.parse(res.body)).to.deep.equal({ locked: true });
            });

            cy.exec('docker exec application php index.php db:unlock-migration', {
                failOnNonZeroExit: false,
            }).then((result) => {
                expect(result.exitCode).to.eq(0);
                expect(result.stdout).to.include('Migration table unlocked');
            });

            isLocked().then((locked) => expect(locked).to.eq(false));
        });

        it('reports "not locked" when there is nothing to unlock', () => {
            isLocked().then((locked) => expect(locked).to.eq(false));

            cy.exec('docker exec application php index.php db:unlock-migration', {
                failOnNonZeroExit: false,
            }).then((result) => {
                expect(result.exitCode).to.eq(0);
                expect(result.stdout).to.include('Migration table is not locked');
            });
        });
    });

    describe('Status', () => {
        it("should display the migration status correctly", () => {
            cy.exec("docker exec application php index.php db:status", {
                failOnNonZeroExit: false,
            }).then((result) => {
                expect(result.stdout).to.include("Migration Lock Status: UNLOCKED");
            });
        });
    });

});
