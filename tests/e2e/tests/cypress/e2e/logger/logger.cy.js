const getDatabaseLogs = () => {
    return cy.request('/logger/getDatabaseLogs').then((res) =>
        typeof res.body === 'string' ? JSON.parse(res.body) : res.body
    );
};

const CONFIG_PATH = '../z_config/z_settings.ini';

const setConfigSetting = (key, value) => {
    cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
        const updated = content.replace(
            new RegExp(`^${key}\\s*=.*`, 'm'),
            `${key} = ${value}`
        );
        cy.writeFile(CONFIG_PATH, updated);
    });
};

// ─── Tests ───────────────────────────────────────────────────────────────────
describe('Controllers', () => {

    let originalConfig;

    before(() => {
        cy.dbSeed();

        // Backup original config to restore it after tests
        cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
            originalConfig = content;
        });
    });

    // Restore original config after all tests have run
    after(() => cy.writeFile(CONFIG_PATH, originalConfig));

    // ── Database ──────────────────────────────────────────────────────────────
    describe("Database", () => {

        // Helper function to assert log values
        const assertLogValues = (logs, { userId = null, name = 'app', method = 'info' } = {}) => {
            const singleLog = logs[0];
            const value = JSON.parse(singleLog.value);

            expect(value.environment.userId).to.equal(userId);
            expect(value.environment.execUserId).to.equal(userId);
            expect(value.message).to.equal(`This is a test log for cypress e2e testing`);


            expect(value.level_name).to.equal(method.toUpperCase());

            expect(value.context.stringInput).to.equal("test");
            expect(value.context.numberInput).to.equal(123);
            expect(value.context.booleanInput).to.equal(true);
            expect(value.context.arrayInput).to.deep.equal([1, 2, 3]);
        };

        // Set logger type to database before tests and clear logs before each test
        before(() => setConfigSetting('logger_type', 'database'));
        beforeEach(() => cy.visit("/logger/clearDatabaseLogs"));

        const cases = [
            { label: 'without being logged in', name: 'zubzet', loggedIn: false, method: 'info' },
            { label: 'while being logged in', name: 'zubzet', loggedIn: true, method: 'warning' },
            { label: 'without being logged in', name: null, loggedIn: false, method: 'emergency' },
            { label: 'while being logged in', name: null, loggedIn: true, method: 'debug' },
        ];

        cases.forEach(({ label, name, loggedIn, method }) => {
            it(`should be possible to log something into the Database ${label}`, () => {
                if(loggedIn) cy.loginAs("admin");

                let baseUrl = "/logger/log?method=" + method;
                if(name) baseUrl += `&name=${name}`;
                cy.visit(baseUrl);

                getDatabaseLogs().then((logs) =>
                    assertLogValues(logs, {
                        userId: loggedIn ? 1 : null,
                        name: name ?? 'app',
                        method,
                    })
                );
            });
        });
    });

    // ── Stream - File ─────────────────────────────────────────────────────────
    describe("Stream - File", () => {

        const FILE_LOG_PATH = '../z_config/app.log';

        const getFileLog = () => cy.readFile(FILE_LOG_PATH, 'utf8');

        const assertFileLog = (content, name = 'app', method = 'info') => {
            let json = JSON.parse(content);

            expect(json.channel).to.equal(name ?? "app");
            expect(json.level_name).to.equal(method.toUpperCase());
        };

        before(() => {
            setConfigSetting('logger_type', 'stream');
            setConfigSetting('logger_stream_url', 'z_config/app.log');
        });

        beforeEach(() => cy.exec(`rm -f ${FILE_LOG_PATH}`));
        after(() => cy.exec(`rm -f ${FILE_LOG_PATH}`));

        const cases = [
            { label: 'without being logged in', name: null, loggedIn: false, method: 'info' },
            { label: 'while being logged in', name: null, loggedIn: true, method: 'warning' },
            { label: 'without being logged in', name: 'zubzet', loggedIn: false, method: 'emergency' },
            { label: 'while being logged in', name: 'zubzet', loggedIn: true, method: 'debug' },
        ];

        cases.forEach(({ label, name, loggedIn, method }) => {
            it(`should be possible to log something into a file stream ${label}`, () => {
                if(loggedIn) cy.loginAs("admin");

                let baseUrl = "/logger/log?method=" + method;
                if(name) baseUrl += `&name=${name}`;
                cy.visit(baseUrl);

                getFileLog().then((content) => assertFileLog(content, name ?? 'app', method));
            });
        });
    });

    describe("Logger Level", () => {

        it("should only log messages that are above the configured logger level", () => {
            setConfigSetting('logger_type', 'database');
            setConfigSetting('logger_level', 300); // Set to DEBUG level
            cy.visit("/logger/clearDatabaseLogs")

            cy.visit("/logger/log?method=info&name=test");
            cy.visit("/logger/log?method=warning&name=test");
            cy.visit("/logger/log?method=error&name=test");

            getDatabaseLogs().then((logs) => {
                let value1 = JSON.parse(logs[0].value);
                let value2 = JSON.parse(logs[1].value);

                expect(logs).to.have.length(2);
                expect(value1.level_name).to.include('WARNING');
                expect(value2.level_name).to.include('ERROR');
            });
        });

    });
});