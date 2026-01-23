describe('Migration System - Import', () => {

    let baseDir = '../app/Database/seed';
    let fixturesDir = "cypress/fixtures";

    const allFiles = [
        "TestSeeding.sql",
        "TestSeeding.php"
    ];

    it("should check if the seeding works correctly", () => {
        cy.dbSeed();

        allFiles.forEach((file) => {
            const source = `${fixturesDir}/MigrationFiles/Seeding/${file}`
            const target = `${baseDir}/${file}`

            cy.exec(`cp ${source} ${target}`);
        });

        cy.exec('docker exec application php index.php db:seed', { failOnNonZeroExit: false });
        cy.visit("/migration/checkSeeding");
        cy.contains("Seed Entry 1");
        cy.contains("Seed Entry 2");
    });

    after(() => {
        allFiles.forEach((file) => {
            const target = `${baseDir}/${file}`
            cy.exec(`rm -f ${target} || true`);
        });
    });
});