describe('Migration System - Sync', () => {

    before(() => {
        cy.dbSeed();

        syncFiles.forEach(({file, table}) => {
            const source = `${fixturesDir}/${file}`;
            const target = `${baseDir}/${file}`;

            cy.exec(`cp ${source} ${target}`);
        });
    });

    after(() => {
        syncFiles.forEach(({file, table}) => {
            const target = `${baseDir}/${file}`;

            cy.exec(`rm -f ${target} || true`);
        });

        cy.exec(`rm -f ../../../src/IncludedComponents/database/Migration/2025-01-01_Ex_Ex.sql || true`);
    });

    let baseDir = '../app/Database/migrations';
    let fixturesDir = "cypress/fixtures/MigrationFiles/SyncFiles";

    const syncFiles = [
        { "file": "2005-01-01_1_Syn_File.sql", "table": "migration_sync"},
        { "file": "2005-01-01_2_Syn_File.sql", "table": "migration_sync_1"},
        { "file": "2005-01-05_1_Syn_File.sql", "table": "migration_sync_2"},
        { "file": "2005-01-10_1_Syn_File.sql", "table": "migration_sync_3"},
        { "file": "2005-01-10_2_Syn_File.sql", "table": "migration_sync_4"},
        { "file": "2005-01-10_3_Syn_File.php", "table": "migration_sync_5"},
    ];

    it('should not be possible to import migrations with only startVersion or endVersion set', () => {
        cy.exec('docker exec application php index.php db:sync --startVersion 1', { failOnNonZeroExit: false }).then((result) => {
            expect(result.stdout).to.include('Cannot use start version without specifying a start date.');
        });

        cy.exec('docker exec application php index.php db:sync --endVersion 1', { failOnNonZeroExit: false }).then((result) => {
            expect(result.stdout).to.include('Cannot use end version without specifying an end date.');
        });
    });

     const statements = {
        'docker exec application php index.php db:sync': {
            "exclude": [],
            "include": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ]
        },
        'docker exec application php index.php db:sync --dry': {
            "exclude": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ],
            "include": []
        },
        'docker exec application php index.php db:sync --start "2005-01-10"': {
            "exclude": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql"
            ],
            "include": [
                "2005-01-10_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ]
        },

        'docker exec application php index.php db:sync --start "2005-01-01" --startVersion 2': {
            "exclude": [
                "2005-01-01_1_Syn_File.sql"
            ],
            "include": [
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ]
        },

        'docker exec application php index.php db:sync --end "2005-01-09"': {
            "exclude": [
                "2005-01-10_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ],
            "include": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql"
            ],
        },

        'docker exec application php index.php db:sync --end "2005-01-10" --endVersion 1': {
            "exclude": [
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ],
            "include": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_Syn_File.sql"
            ]
        },

        'docker exec application php index.php db:sync --start "2005-01-05" --end "2005-01-09"': {
            "exclude": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-01_2_Syn_File.sql",
                "2005-01-10_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ],
            "include": [
                "2005-01-05_1_Syn_File.sql"
            ]
        },

        'docker exec application php index.php db:sync --start "2005-01-01" --startVersion 2 --end "2005-01-10" --endVersion 1': {
            "exclude": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ],
            "include": [
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_Syn_File.sql"
            ],
        },

        'docker exec application php index.php db:sync -i default -i production': {
            "exclude": [],
            "include": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql",
                "2005-01-10_3_Syn_File.php"
            ]
        },

        'docker exec application php index.php db:sync -e production': {
            "exclude": [
                "2005-01-10_3_Syn_File.php"
            ],
            "include": [
                "2005-01-01_1_Syn_File.sql",
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_Syn_File.sql",
                "2005-01-10_2_Syn_File.sql"
            ]
        }
    };

    const notIncludedTables = [
        "migration_sync",
        "migration_sync_1",
        "migration_sync_2",
        "migration_sync_3",
        "migration_sync_4",
        "migration_sync_5"
    ];


    it('should sync specified migrations', () => {
        Object.entries(statements).forEach(([command, expectations]) => {
            cy.dbSeed();

            cy.exec(command, { failOnNonZeroExit: false }).then((result) => {
                console.log(result);
            });;

            cy.request('/migration/syncMigrations').then((res) => {
                let jsonData = JSON.parse(res.body);

                const versions = jsonData.versions.map(v => v.migration_name);
                const tables = (jsonData.tables || []).map(t => t.Tables_in_app);

                // Prüfen, was enthalten sein MUSS
                expectations.include.forEach((file) => {
                    expect(versions, `File ${file} sollte vorhanden sein`).to.include(file);
                });

                // Prüfen, was NICHT enthalten sein DARF
                expectations.exclude.forEach((file) => {
                    expect(versions, `File ${file} sollte ausgeschlossen sein`).to.not.include(file);
                });

                notIncludedTables.forEach((table) => {
                    expect(tables).to.not.include(table);
                });
            });
        });
    });

    let zubzetMigrationPath = "../../../src/IncludedComponents/database/Migration";

    it("should check the --include-external option", () => {
        cy.dbSeed();
        const file = "2025-01-01_Ex_Ex.sql";

        cy.exec(`cp ${fixturesDir}/${file} ${zubzetMigrationPath}/${file}`);
        cy.exec('docker exec application php index.php db:sync --include-external');

        cy.request('/migration/syncMigrations').then((res) => {

            expect(res.body).to.include("2025-01-01_Ex_Ex.sql");
        });

        cy.exec(`rm -f ${zubzetMigrationPath}/${file} || true`);
    });
});