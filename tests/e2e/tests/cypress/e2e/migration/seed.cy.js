describe('Migration System - Import', () => {

    const baseDir = '../app/Database/seed';
    const fixturesDir = "cypress/fixtures";

    const allFiles = [
        "TestSeeding.sql",
        "TestSeeding.php",
        "Environments/Staging/Chat.sql",
        "Environments/Staging/Departments.sql",
        "Environments/Testing/Chat.sql",
        "Environments/Testing/Departments.sql",
    ];

    const copySeedFiles = () => {
        allFiles.forEach((file) => {
            const source = `${fixturesDir}/MigrationFiles/Seeding/${file}`;
            const target = `${baseDir}/${file}`;
            const targetDir = target.substring(0, target.lastIndexOf('/'));

            cy.exec(`mkdir -p ${targetDir}`);
            cy.exec(`cp ${source} ${target}`);
        });
    };

    const removeSeedFiles = () => {
        allFiles.forEach((file) => {
            const target = `${baseDir}/${file}`;
            cy.exec(`rm -f ${target} || true`);
        });
    };

    beforeEach(() => {
        cy.dbSeed();
        copySeedFiles();
    });

    it("should check if the seeding works correctly", () => {
        cy.exec('docker exec application php index.php db:seed', { failOnNonZeroExit: false });
        cy.visit("/migration/checkSeeding");

        cy.contains("Seed Entry 1");
        cy.contains("Seed Entry 2");
        cy.contains("Seed Entry Staging Chat");
        cy.contains("Seed Entry Staging Departments");
        cy.contains("Seed Entry Testing Chat");
        cy.contains("Seed Entry Testing Departments");
    });

    it("should allow excluding and re-including specific seed environments", () => {
        cy.exec('docker exec application php index.php db:seed -e Environments -i Environments/Testing', { failOnNonZeroExit: false });
        cy.visit("/migration/checkSeeding");

        cy.contains("Seed Entry 1");
        cy.contains("Seed Entry 2");
        cy.contains("Seed Entry Testing Chat");
        cy.contains("Seed Entry Testing Departments");

        cy.contains("Seed Entry Staging Chat").should("not.exist");
        cy.contains("Seed Entry Staging Departments").should("not.exist");
    });

    it("should allow re-including a single seed file", () => {
        cy.exec('docker exec application php index.php db:seed -e Environments -i Environments/Testing/Chat.sql', { failOnNonZeroExit: false });
        cy.visit("/migration/checkSeeding");

        cy.contains("Seed Entry 1");
        cy.contains("Seed Entry 2");
        cy.contains("Seed Entry Testing Chat");

        cy.contains("Seed Entry Testing Departments").should("not.exist");
        cy.contains("Seed Entry Staging Chat").should("not.exist");
        cy.contains("Seed Entry Staging Departments").should("not.exist");
    });

    it("should be possible to run the seeding without running the migrations first", () => {
        cy.exec('docker exec application php index.php db:seed --skip-migrations', { failOnNonZeroExit: false }).then((result) => {
            expect(result.stdout).to.not.include('Running migrations before seeding...');
        });
    });

    after(() => {
        removeSeedFiles();
    });
});