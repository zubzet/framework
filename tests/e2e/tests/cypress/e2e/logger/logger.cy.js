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

// Helper: pick logs whose decoded `message` matches
const logsByMessage = (logs, message) =>
    logs
        .map((l) => JSON.parse(l.value))
        .filter((v) => v.message === message);

describe('Logger', () => {

    let originalConfig;

    before(() => {
        cy.dbSeed();

        // Backup original config to restore it after tests
        cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
            originalConfig = content;
        });
    });

    // Restore original config after all tests have run
    after(() => {
        cy.writeFile(CONFIG_PATH, originalConfig);
    });

    describe("Methods", () => {
        describe("Database", () => {

            // Helper function to assert log values
            const assertLogValues = (logs, { userId = null, name = 'app', method = 'info' } = {}) => {
                // SLOW_REQUEST entries from the preceding clearDatabaseLogs
                // request (or this request) can land in the table under
                // coverage, so locate the action_log entry by its message
                // rather than assuming logs[0].
                const value = logs
                    .map((l) => JSON.parse(l.value))
                    .find((v) => v.message === "This is a test log for cypress e2e testing");

                expect(value, 'action_log entry in DB').to.exist;

                expect(value.extra.userId).to.equal(userId);
                expect(value.extra.execUserId).to.equal(userId);
                expect(value.extra.source).to.equal("web");

                expect(value.message).to.equal(`This is a test log for cypress e2e testing`);
                expect(value.channel).to.equal(name);
                expect(value.level_name).to.equal(method.toUpperCase());

                expect(value.context.stringInput).to.equal("test");
                expect(value.context.numberInput).to.equal(123);
                expect(value.context.booleanInput).to.equal(true);
                expect(value.context.arrayInput).to.deep.equal([1, 2, 3]);

                // Backtrace assertions
                expect(value.extra).to.have.property('file').that.includes('LoggerController.php');
                expect(value.extra).to.have.property('line').that.equals(21);
                expect(value.extra).to.have.property('class').that.equals('LoggerController');
                expect(value.extra).to.have.property('function').that.equals('action_log');
                expect(value.extra).to.not.have.property('callType');
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
                it(`should be possible to log something into the Database ${label} with logger name ${name}`, () => {
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

        describe("Stream - File", () => {

            const FILE_LOG_PATH = '../z_config/app.log';

            const getFileLog = () => cy.readFile(FILE_LOG_PATH, 'utf8');

            const assertFileLog = (content, name = 'app', method = 'info') => {
                // The stream logger writes one JSON object per line. Other
                // framework log calls in the same request (SLOW_QUERY /
                // SLOW_REQUEST under coverage, auth events for logged-in
                // users, etc.) end up in the same file, so pick the line
                // emitted by LoggerController::action_log instead of
                // assuming a single-line file.
                const json = content
                    .split(/\r?\n/)
                    .filter((line) => line.trim().length > 0)
                    .map((line) => JSON.parse(line))
                    .find((entry) => entry.extra?.function === 'action_log');

                expect(json, 'action_log entry in stream file').to.exist;
                expect(json.channel).to.equal(name ?? "app");
                expect(json.level_name).to.equal(method.toUpperCase());

                // Backtrace assertions
                expect(json.extra).to.have.property('file').that.includes('LoggerController.php');
                expect(json.extra).to.have.property('line').that.equals(21);
                expect(json.extra).to.have.property('class').that.equals('LoggerController');
                expect(json.extra).to.have.property('function').that.equals('action_log');
                expect(json.extra).to.not.have.property('callType');
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
                it(`should be possible to log something into a file stream ${label} with logger name ${name}`, () => {
                    if(loggedIn) cy.loginAs("admin");

                    let baseUrl = "/logger/log?method=" + method;
                    if(name) baseUrl += `&name=${name}`;
                    cy.visit(baseUrl);

                    getFileLog().then((content) => assertFileLog(content, name ?? 'app', method));
                });
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

    describe("TraceId", () => {
        before(() => {
            setConfigSetting('logger_type', 'database');
            setConfigSetting('logger_level', 'debug');
        });
        beforeEach(() => cy.visit("/logger/clearDatabaseLogs"));

        it("should assign the same traceId to every log within a single request", () => {
            cy.visit("/logger/multiLog");
            getDatabaseLogs().then((logs) => {
                expect(logs).to.have.length(2);
                const v1 = JSON.parse(logs[0].value);
                const v2 = JSON.parse(logs[1].value);
                expect(v1.extra.traceId).to.be.a('string').and.have.length.greaterThan(0);
                expect(v1.extra.traceId).to.equal(v2.extra.traceId);
            });
        });

        it("should assign different traceIds to separate requests", () => {
            cy.visit("/logger/multiLog");
            cy.visit("/logger/multiLog");
            getDatabaseLogs().then((logs) => {
                expect(logs).to.have.length(4);
                const traceIds = [...new Set(logs.map((l) => JSON.parse(l.value).extra.traceId))];
                expect(traceIds).to.have.length(2);
            });
        });

        it("should expose the current traceId via Logger::getTraceId()", () => {
            cy.request('/logger/getTraceId').then((res) => {
                const returned = (typeof res.body === 'string' ? res.body : String(res.body)).trim();
                expect(returned).to.match(/^[0-9a-f]{32}$/);
                getDatabaseLogs().then((logs) => {
                    expect(logs).to.have.length(1);
                    expect(JSON.parse(logs[0].value).extra.traceId).to.equal(returned);
                });
            });
        });

        it("should honor Logger::setTraceId()", () => {
            const custom = 'a'.repeat(32);
            cy.visit(`/logger/setTraceId?trace=${custom}`);
            getDatabaseLogs().then((logs) => {
                expect(logs).to.have.length(1);
                expect(JSON.parse(logs[0].value).extra.traceId).to.equal(custom);
            });
        });
    });

    describe("Context", () => {
        before(() => {
            setConfigSetting('logger_type', 'database');
            setConfigSetting('logger_level', 'debug');
        });
        beforeEach(() => cy.visit("/logger/clearDatabaseLogs"));

        it("should carry added context forward across log calls on the same logger", () => {
            cy.visit("/logger/context");
            getDatabaseLogs().then((logs) => {
                expect(logs).to.have.length(2);
                logs.forEach((l) => {
                    expect(JSON.parse(l.value).extra.x).to.equal(1);
                });
            });
        });

        it("should let contextInspect mutate the stored context", () => {
            cy.visit("/logger/contextInspect");
            getDatabaseLogs().then((logs) => {
                expect(logs).to.have.length(1);
                const extra = JSON.parse(logs[0].value).extra;
                expect(extra.x).to.equal(1);
                expect(extra.y).to.equal(2);
            });
        });

        it("should merge context from another logger with source-wins precedence", () => {
            cy.visit("/logger/contextMerge");
            getDatabaseLogs().then((logs) => {
                expect(logs).to.have.length(1);
                const extra = JSON.parse(logs[0].value).extra;
                // merged-from ("source") takes precedence on shared keys
                expect(extra.shared).to.equal("from-source");
                // target keeps its own non-conflicting keys
                expect(extra.y).to.equal(2);
                // source keys are added into target
                expect(extra.x).to.equal(1);
            });
        });

        it("should drop prior context after contextClear", () => {
            cy.visit("/logger/contextClear");
            getDatabaseLogs().then((logs) => {
                expect(logs).to.have.length(1);
                const extra = JSON.parse(logs[0].value).extra;
                expect(extra).to.not.have.property('x');
            });
        });

        it("should throw InvalidArgumentException when contextMergeFrom is called with an empty string", () => {
            cy.request({ url: '/logger/contextMergeFromEmpty', failOnStatusCode: false });
            getDatabaseLogs().then((logs) => {
                const e = logsByMessage(logs, 'EXCEPTION');
                expect(e).to.have.length(1);
                expect(e[0].context.class).to.equal('InvalidArgumentException');
            });
        });

        it("should throw when contextMergeFrom references a logger that has not been created", () => {
            cy.request({ url: '/logger/contextMergeFromMissing', failOnStatusCode: false });
            getDatabaseLogs().then((logs) => {
                const e = logsByMessage(logs, 'EXCEPTION');
                expect(e).to.have.length(1);
                expect(e[0].context.message).to.contain('does-not-exist');
            });
        });
    });

    describe("Auto-log", () => {
        // Slow-query / slow-request thresholds default to 30000ms in
        // z_settings.ini so coverage-overhead requests don't pollute the log
        // table across the rest of the suite. These describes lower the
        // threshold inside each test body so the beforeEach clearDatabaseLogs
        // visit (which itself ends a request) runs with the 30000 default.
        //
        // Robustness under xdebug coverage: every intentional slow query
        // here uses `SLEEP(...)`. Background queries from coverage overhead
        // never do. Filtering SLOW_QUERY entries by /SLEEP/i pattern lets the
        // assertions count only the queries the test set up, while ignoring
        // any incidental slow queries that arose from instrumentation.
        // Recursion is still implicitly checked: if the framework's
        // self-logging INSERT recurses on itself, that wouldn't have SLEEP
        // in its query text — but a missing guard would be visible as a
        // separate SLOW_QUERY entry whose query starts with `INSERT INTO`
        // for the log table, which we explicitly assert against below.
        describe("Slow Queries", () => {
            before(() => {
                setConfigSetting('logger_type', 'database');
                setConfigSetting('logger_level', 'debug');
            });
            beforeEach(() => cy.visit("/logger/clearDatabaseLogs"));
            afterEach(() => setConfigSetting('logger_slow_query_ms', '30000'));

            // Helper: pick only the SLEEP queries the test set up.
            const sleepQueries = (logs) =>
                logsByMessage(logs, 'SLOW_QUERY')
                    .filter((q) => /SLEEP/i.test(q.context.query));

            // Helper: assert no recursive logging — the logger's own INSERT
            // into the log table must never appear among the slow queries.
            const noLogTableRecursion = (logs) => {
                const recursive = logsByMessage(logs, 'SLOW_QUERY')
                    .filter((q) => /INTO\s+`?z_log`?/i.test(q.context.query));
                expect(recursive, 'logger should not log its own INSERT').to.have.length(0);
            };

            it("should log exactly one SLOW_QUERY row above the threshold (no recursion)", () => {
                setConfigSetting('logger_slow_query_ms', '300');
                cy.visit("/logger/slowQuery");
                getDatabaseLogs().then((logs) => {
                    const slow = sleepQueries(logs);
                    expect(slow).to.have.length(1);
                    expect(slow[0].level_name).to.equal('WARNING');
                    expect(slow[0].channel).to.equal('zubzet');
                    expect(slow[0].context.duration_ms).to.be.greaterThan(400);
                    expect(slow[0].context.query).to.match(/SLEEP/i);
                    noLogTableRecursion(logs);
                });
            });

            it("should preserve insertId when the outer INSERT crosses the slow-query threshold", () => {
                setConfigSetting('logger_slow_query_ms', '300');
                cy.request('/logger/slowInsertId').then((res) => {
                    const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
                    expect(body.insertId).to.equal(3);
                });
                getDatabaseLogs().then((logs) => {
                    expect(sleepQueries(logs)).to.have.length(1);
                    noLogTableRecursion(logs);
                });
            });

            it("should preserve result rows when the outer SELECT crosses the slow-query threshold", () => {
                setConfigSetting('logger_slow_query_ms', '300');
                cy.request('/logger/slowSelectResult').then((res) => {
                    const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
                    expect(body.rows).to.have.length(1);
                    expect(Number(body.rows[0].answer)).to.equal(42);
                });
                getDatabaseLogs().then((logs) => {
                    expect(sleepQueries(logs)).to.have.length(1);
                    noLogTableRecursion(logs);
                });
            });
        });

        describe("Slow Request", () => {
            before(() => {
                setConfigSetting('logger_type', 'database');
                setConfigSetting('logger_level', 'debug');
            });
            beforeEach(() => cy.visit("/logger/clearDatabaseLogs"));
            afterEach(() => setConfigSetting('logger_slow_request_ms', '30000'));

            it("should log a SLOW_REQUEST on the zubzet channel above the threshold", () => {
                setConfigSetting('logger_slow_request_ms', '1');
                cy.visit("/logger/slowRequest");
                getDatabaseLogs().then((logs) => {
                    const slow = logsByMessage(logs, 'SLOW_REQUEST');
                    expect(slow).to.have.length(1);
                    expect(slow[0].channel).to.equal('zubzet');
                    expect(slow[0].level_name).to.equal('WARNING');
                    expect(slow[0].context.duration_ms).to.be.at.least(1);
                    expect(slow[0].context.uri).to.equal('logger/slowRequest');
                });
            });

            it("should NOT log a SLOW_REQUEST when the request ended in an uncaught exception", () => {
                setConfigSetting('logger_slow_request_ms', '1');
                cy.request({ url: '/logger/slowRequestThenException', failOnStatusCode: false });
                getDatabaseLogs().then((logs) => {
                    expect(logsByMessage(logs, 'SLOW_REQUEST')).to.have.length(0);
                    expect(logsByMessage(logs, 'EXCEPTION')).to.have.length(1);
                });
            });
        });

        describe("Warnings & Deprecations", () => {
            before(() => {
                setConfigSetting('logger_type', 'database');
                setConfigSetting('logger_level', 'debug');
            });
            beforeEach(() => cy.visit("/logger/clearDatabaseLogs"));

            it("should log E_USER_DEPRECATED as message=LogEventType::DEPRECATION at NOTICE level", () => {
                cy.request({ url: '/logger/deprecation', failOnStatusCode: false });
                getDatabaseLogs().then((logs) => {
                    const d = logsByMessage(logs, 'DEPRECATION');
                    expect(d).to.have.length(1);
                    expect(d[0].level_name).to.equal('NOTICE');
                    expect(d[0].channel).to.equal('zubzet');
                    expect(d[0].context.message).to.contain('old API usage');
                });
            });

            it("should NOT log errors suppressed with the @ operator", () => {
                cy.request({ url: '/logger/suppressedWarning', failOnStatusCode: false });
                getDatabaseLogs().then((logs) => {
                    expect(logsByMessage(logs, 'WARNING')).to.have.length(0);
                    expect(logsByMessage(logs, 'ERROR')).to.have.length(0);
                });
            });
        });

        describe("Uncaught Exceptions", () => {
            before(() => {
                setConfigSetting('logger_type', 'database');
                setConfigSetting('logger_level', 'debug');
            });
            beforeEach(() => cy.visit("/logger/clearDatabaseLogs"));

            it("should log uncaught exceptions with full trace at ERROR level", () => {
                cy.request({ url: '/logger/uncaughtException', failOnStatusCode: false });
                getDatabaseLogs().then((logs) => {
                    const e = logsByMessage(logs, 'EXCEPTION');
                    expect(e).to.have.length(1);
                    expect(e[0].level_name).to.equal('ERROR');
                    expect(e[0].channel).to.equal('zubzet');
                    expect(e[0].context.class).to.equal('RuntimeException');
                    expect(e[0].context.message).to.equal('boom');
                    expect(e[0].context.trace).to.be.a('string').and.have.length.greaterThan(0);
                });
            });
        });
    });
});