describe('Migration System - Import', () => {
    let baseDir = '../app/Database/migrations';
    let fixturesDir = "cypress/fixtures";

    let zubzetMigrationPath = "../../../src/default/database/Migration";

    before(() => {
        cy.dbSeed();
    });

    const allFiles = [
        "Syntax.sql",
        "2025-10-10.sql",
        "12-10-2004_Wrong_Date.sql",
        "1990-10-10_Test_Name.sql",
        "5000-10-10_Test_Name.sql",

        "2005-01-01_1_Syn_File.sql",
        "2005-01-01_2_Syn_File.sql",
        "2005-01-05_1_Syn_File.sql",
        "2005-01-10_1_Syn_File.sql",
        "2005-01-10_2_Syn_File.sql",

        "2025-09-30_MigrationPHPImport.sql",
        "2025-10-01_FaultyMigration.sql",
        "2025-10-01_MigrationEnv.php",
        "2025-10-01_MigrationEnv1.php",
        "2025-10-01_MigrationImport.php",
        "2025-10-01_MigrationImport.sql",
        "2025-10-01_MigrationManual.php",
        "2025-10-01_MigrationSkip.php",
        "2025-10-01_MigrationPHPImport.php",
        "2025-10-01_SkippedMigration.sql"
    ];

    const zubzetMigration = [
        "2025-10-01_MigrationImport.sql"
    ];

    after(() => {
        // Clean up all copied files
        allFiles.forEach((file) => {
            cy.exec(`rm -f ${baseDir}/${file} || true`, { failOnNonZeroExit: false });
        });

        zubzetMigration.forEach((file) => {
            cy.exec(`rm -f ${zubzetMigrationPath}/${file} || true`, { failOnNonZeroExit: false });
        });
    });

    it('should check the Migration-File names correctly', () => {
         const base = 'MigrationFiles/NameValidation';

         const migrationFiles = [
            { file: "Syntax.sql", expectedError: "Formatting error: 'Syntax.sql'. Expected: YYYY-MM-DD_Name" },
            { file: "2025-10-10.sql", expectedError: "Formatting error: '2025-10-10.sql'. Expected: YYYY-MM-DD_Name" },
            { file: "12-10-2004_Wrong_Date.sql", expectedError: "Syntax error: '12-10-2004_Wrong_Date.sql'. Date must be exactly YYYY-MM-DD." },
            { file: "1990-10-10_Test_Name.sql", expectedError: "History error: The year 1990 is too far in the past. " },
            { file: "5000-10-10_Test_Name.sql", expectedError: "Future error: The date '5000-10-10' is in the future." }
         ];

        migrationFiles.forEach(({ file, expectedError }) => {
            const source = `${fixturesDir}/${base}/${file}`
            const target = `${baseDir}/${file}`

            cy.exec(`cp ${source} ${target}`);
            cy.exec('docker exec application php index.php db:import -f',{ failOnNonZeroExit: false }).then((result) => {
                expect(result.stderr).to.include(expectedError);
            });

            cy.exec(`rm -f ${target} || true`);
        });
    });

    // It should show the exact File and Error if a SQL-Migration fails
    it('should show error for faulty SQL migration', () => {
        let source = `${fixturesDir}/MigrationFiles/2025-10-01_FaultyMigration.sql`;
        let target = `${baseDir}/2025-10-01_FaultyMigration.sql`;

        cy.exec(`cp ${source} ${target}`);
        cy.exec('docker exec application php index.php db:import -f',{ failOnNonZeroExit: false }).then((result) => {
            expect(result.stdout).to.include(
                "Error importing ./app/Database/migrations/2025-10-01_FaultyMigration.sql: You have an error in your SQL syntax;"
            );
        });

        cy.exec(`rm -f ${target} || true`);
    });

    // Check if the Files are actually executed and imported correctly
    it('should import valid SQL migration correctly', () => {
        let base = 'MigrationFiles';

        const migrationFiles = [
            "2025-10-01_MigrationImport.sql",
            "2025-10-01_MigrationImport.php"
        ];

        migrationFiles.forEach((file) => {
            cy.dbSeed();
            const source = `${fixturesDir}/${base}/${file}`;
            const target = `${baseDir}/${file}`;

            cy.exec(`cp ${source} ${target}`);
            cy.exec('docker exec application php index.php db:import -f');
            cy.visit("/migration/checkImport");

            cy.contains('{"entries":[{"id":1,"name":"Test Entry 1"},{"id":2,"name":"Test Entry 2"}],"columns":[{"Field":"id","Type":"int(11)","Null":"NO","Key":"PRI","Default":null,"Extra":"auto_increment"},{"Field":"name","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""}]}');

            cy.exec(`rm -f ${target} || true`);
        });
    });

    it('should check the single PHP Statements', () => {
        cy.dbSeed();

        let base = 'MigrationFiles';

        const migrationFiles = [
            "2025-10-01_MigrationPHPImport.php",
            "2025-09-30_MigrationPHPImport.sql"
        ];

        migrationFiles.forEach((file) => {
            const source = `${fixturesDir}/${base}/${file}`;
            const target = `${baseDir}/${file}`;

            cy.exec(`cp ${source} ${target}`);
        });

        cy.exec('docker exec application php index.php db:import -f');
        cy.visit("/migration/checkPHPFiles");

        cy.contains('[{"Field":"id","Type":"int(11)","Null":"NO","Key":"PRI","Default":null,"Extra":"auto_increment"},{"Field":"name","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""},{"Field":"description","Type":"longtext","Null":"YES","Key":"","Default":null,"Extra":""}]');

        migrationFiles.forEach((file) => {
             const target = `${baseDir}/${file}`;
             cy.exec(`rm -f ${target} || true`);
        });
    });

    it("should check the -dry run option", () => {
        cy.dbSeed();

        const file = "2025-10-01_MigrationImport.sql";

        cy.exec(`cp ${fixturesDir}/MigrationFiles/${file} ${baseDir}/${file}`);
        cy.exec('docker exec application php index.php db:import --dry -f');
        cy.visit("/migration/checkImport");

        cy.contains("Table 'app.migration_import' doesn't exist");

        cy.exec(`rm -f ${baseDir}/${file} || true`);
    });

    it("should skip the marked migrations", () => {
        const file = "2025-10-01_MigrationSkip.php";

        cy.exec(`cp ${fixturesDir}/MigrationFiles/${file} ${baseDir}/${file}`);
        cy.exec('docker exec application php index.php db:import -f');
        cy.visit("/migration/checkSkippedMigrations");

        cy.contains("Table 'app.migration_skip' doesn't exist");

        cy.exec(`rm -f ${baseDir}/${file} || true`);
    });

    it("should show manual migrations in the report", () => {
        const file = "2025-10-01_MigrationManual.php";

        cy.exec(`cp ${fixturesDir}/MigrationFiles/${file} ${baseDir}/${file}`);
        cy.exec('docker exec application php index.php db:import -f', { failOnNonZeroExit: false }).then((result) => {
            expect(result.stdout).to.include("Migration requires manual execution: ./app/Database/migrations/2025-10-01_MigrationManual.php");
        });

        cy.exec(`rm -f ${baseDir}/${file} || true`);
    });

    it("should ignore environments not included in migrations", () => {
        cy.dbSeed();

        const file = "2025-10-01_MigrationEnv.php";

        cy.exec(`cp ${fixturesDir}/MigrationFiles/${file} ${baseDir}/${file}`);
        cy.exec('docker exec application php index.php db:import -i default -i production -f');
        cy.visit("/migration/checkEnvMigrations");

        cy.contains("Table 'app.migration_env' doesn't exist").should("not.exist");

        cy.exec(`rm -f ${baseDir}/${file} || true`);
    });

    it("should import environments not excluded in migrations", () => {
        cy.dbSeed();

        const file = "2025-10-01_MigrationEnv1.php";

        cy.exec(`cp ${fixturesDir}/MigrationFiles/${file} ${baseDir}/${file}`);
        cy.exec('docker exec application php index.php db:import -e production -f');
        cy.visit("/migration/checkEnvMigrations");

        cy.contains("Table 'app.migration_env' doesn't exist");

        cy.exec(`rm -f ${baseDir}/${file} || true`);
    });

    it("should check if some migrations were skipped", () => {
        const file = "2025-10-01_SkippedMigration.sql";

        cy.exec(`cp ${fixturesDir}/MigrationFiles/${file} ${baseDir}/${file}`);
        cy.exec('docker exec application php index.php db:import', { failOnNonZeroExit: false }).then((result) => {
            expect(result.stdout).to.include("Warning: The following migrations were skipped:\n- SkippedMigration.sql");
        });
    });

    it("should check the --exclude-external option", () => {
        cy.dbSeed();

        const file = "2025-10-01_MigrationImport.sql";

        cy.exec(`cp ${fixturesDir}/MigrationFiles/${file} ${zubzetMigrationPath}/${file}`);
        cy.exec('docker exec application php index.php db:import --exclude-external -f');
        cy.visit("/migration/checkImport");

        cy.contains("Table 'app.migration_import' doesn't exist");

        cy.exec(`rm -f ${zubzetMigrationPath}/${file} || true`);
    });

    it("should check the --enforce-external-timeline option", () => {
        cy.dbSeed();

        const file = "2025-10-01_MigrationImport.sql";

        cy.exec(`cp ${fixturesDir}/MigrationFiles/${file} ${zubzetMigrationPath}/${file}`);
        cy.exec('docker exec application php index.php db:import --enforce-external-timeline', { failOnNonZeroExit: false }).then((result) => {
            expect(result.stdout).to.include("Warning: The following migrations were skipped:\n- MigrationImport.sql\nAborting import due to skipped migrations.");
        });

        cy.exec(`rm -f ${zubzetMigrationPath}/${file} || true`);
    });

});