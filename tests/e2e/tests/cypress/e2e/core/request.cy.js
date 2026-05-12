describe('Request', () => {

    const files = [
        { name: 'TestFile_Small.txt',   size: 1 },
    ];

    const dir = 'cypress/fixtures';

    before(() => {
        cy.dbSeed();

        // Create files for testing the upload
        files.forEach(file => {
            const bytes = Math.round(file.size * 1024);
            const buf = Cypress.Buffer.alloc(bytes, 0);
            return cy.writeFile(`${dir}/${file.name}`, buf, { encoding: null });
        });
    });

    after(() => {
        // Delete files after testing
        if (Cypress.platform === 'win32') {
            const targets = files.map(f => `"${dir.replace(/\//g, '\\')}\\${f.name}"`).join(' ');
            cy.exec(`del /f /q ${targets} 2>NUL || exit /b 0`);
        } else {
            const targets = files.map(f => `"${dir}/${f.name}"`).join(' ');
            cy.exec(`rm -f ${targets} || true`);
        }
    });


    it('GET', () => {
        cy.visit("/Core/Get?TestGet=JustATest");
        cy.contains("JustATest");
    });

    it('POST', () => {
        cy.request({
            method: 'POST',
            url: '/Core/Post',
            form: true,
            body: {
                TestPost: 'JustATest'
            }
        }).then((response) => {
            expect(response.status).to.eq(200);
            expect(response.body).to.include('JustATest');
        });
    });

    it('FILE', () => {
        cy.fixture('TestFile_Small.txt', 'binary').then((fileContent) => {
            const blob = Cypress.Blob.binaryStringToBlob(fileContent, 'text/plain');

            const formData = new FormData();
            formData.append('file', blob, 'TestFile_Small.txt');

            cy.intercept('POST', '/Core/File').as('fileUpload');

            cy.window().then((win) => {
                const xhr = new win.XMLHttpRequest();
                xhr.open('POST', '/Core/File');
                xhr.onload = function () {
                    expect(xhr.status).to.eq(200);
                    expect(xhr.responseText).to.include('TestFile_Small.txt');
                };
                xhr.send(formData);
            });

            cy.wait('@fileUpload');
        });
    });

    // Request::ip() — walks $_SERVER for HTTP_CLIENT_IP, then
    // HTTP_X_FORWARDED_FOR, then REMOTE_ADDR. Each header priority is
    // exercised by sending a different mix.
    it('ip() resolves from header priority chain', () => {
        const cases = [
            // Case 1: explicit Client-IP header wins (PHP exposes it as HTTP_CLIENT_IP).
            {
                headers: {
                    'Client-IP': '203.0.113.10',
                    'X-Forwarded-For': '198.51.100.20',
                },
                expected: '203.0.113.10',
            },
            // Case 2: no Client-IP, X-Forwarded-For wins.
            {
                headers: { 'X-Forwarded-For': '198.51.100.30' },
                expected: '198.51.100.30',
            },
            // Case 3: no headers at all → falls back to REMOTE_ADDR (the
            // docker bridge IP). Just assert the body is a non-empty IP-ish
            // string; we don't pin the exact bridge value because docker
            // reassigns it.
            {
                headers: {},
                expectedMatch: /^\d+\.\d+\.\d+\.\d+$/,
            },
        ];

        cases.forEach(({ headers, expected, expectedMatch }) => {
            cy.request({
                url: '/Core/clientIp',
                headers,
            }).then((res) => {
                const value = JSON.parse(res.body);
                if (expected !== undefined) {
                    expect(value, JSON.stringify(headers)).to.eq(expected);
                } else {
                    expect(value, JSON.stringify(headers)).to.match(expectedMatch);
                }
            });
        });
    });

    // Request::referer() reads HTTP_REFERER. JSON-encoded so the
    // null-when-absent case is observable (and distinct from a literal "").
    it('referer() reflects the Referer header, and is null when absent', () => {
        cy.request({
            url: '/Core/referer',
            headers: { Referer: 'https://example.com/from?x=1' },
        }).then((res) => {
            expect(JSON.parse(res.body)).to.eq('https://example.com/from?x=1');
        });

        // cy.request doesn't auto-set Referer when no cy.visit precedes it.
        cy.request('/Core/referer').then((res) => {
            expect(JSON.parse(res.body)).to.eq(null);
        });
    });

    // Request::userAgent() reads HTTP_USER_AGENT verbatim. Cypress's
    // HTTP client always sends a UA, so we can't test the null branch
    // here — only the present-header case.
    it('userAgent() returns the User-Agent header verbatim', () => {
        const ua = 'CypressProbe/1.0 (request.cy.js userAgent test)';
        cy.request({
            url: '/Core/userAgent',
            headers: { 'User-Agent': ua },
        }).then((res) => {
            expect(JSON.parse(res.body)).to.eq(ua);
        });
    });

    // Request::getExecutionTime() = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'].
    // The probe accepts ?delay=<ms> and usleep()s before reading, so the value
    // returned must be at least that long.
    it('getExecutionTime() — increases by the in-action sleep duration', () => {
        cy.request('/Core/executionTime').then((res) => {
            const fast = JSON.parse(res.body);
            expect(fast, 'fast call returns a number').to.be.a('number');
            expect(fast).to.be.greaterThan(0);
            expect(fast).to.be.lessThan(15);
        });

        cy.request('/Core/executionTime?delay=200').then((res) => {
            const slow = JSON.parse(res.body);
            // Must reflect the deliberate 200ms sleep before getExecutionTime ran.
            expect(slow).to.be.greaterThan(0.2);
        });
    });

    // Request::getCurrentURL() = config("host") . REQUEST_URI. The e2e
    // container sets CONFIG_HOST=http://localhost:8080 (env-var override of
    // z_settings.ini, see Configuration::loadConfiguration's allow_env_config
    // branch), so the canonical configured host is :8080 here.
    it('getCurrentURL() concatenates config("host") with the request URI', () => {
        cy.request('/Core/currentUrl?foo=bar&baz=1').then((res) => {
            expect(res.body).to.eq('http://localhost:8080/Core/currentUrl?foo=bar&baz=1');
        });
    });

    // Even with a spoofed Host header on the way in, getCurrentURL must
    // return the *configured* host. The override test below proves the
    // stronger property (configured host wins over a totally different
    // transport); this test pins down the no-bleed-through guarantee.
    it('getCurrentURL() ignores the incoming Host header — config("host") wins', () => {
        cy.request({
            url: '/Core/currentUrl?prove=it',
            headers: { Host: 'spoofed.attacker.example' },
        }).then((res) => {
            expect(res.body).to.eq('http://localhost:8080/Core/currentUrl?prove=it');
            expect(res.body).to.not.include('spoofed.attacker.example');
        });
    });

    // Probe mutates zubzet()->host = <newHost> via HasDynamicAttributes
    // __set, then config("host") (read through __get) returns the new
    // value within this single request. Proves the round-trip works end
    // to end.
    it('getCurrentURL() picks up a DynamicAttributes host override mid-request', () => {
        const fakeHost = 'https://prod.example.com';
        const encoded = encodeURIComponent(fakeHost);
        cy.request(`/Core/currentUrl?hostOverride=${encoded}`).then((res) => {
            expect(res.body).to.eq(`${fakeHost}/Core/currentUrl?hostOverride=${encoded}`);
        });
    });

    // Request::getDomain() strips scheme, path, and port from config("host").
    // Override the host via DynamicAttributes for the in-request value so we
    // can sweep a wider variety of shapes than the single INI default.
    it('getDomain() strips scheme/port/trailing slash across host shapes', () => {
        const cases = [
            // Default (no override) — z_settings.ini host=http://localhost:4000.
            [null,                              'localhost'],
            // Plain http URL.
            ['http://example.com',              'example.com'],
            // Plain https URL.
            ['https://example.com',             'example.com'],
            // Subdomain + custom port + trailing slash — port and slash stripped.
            ['https://api.example.com:8443/',   'api.example.com'],
            // No scheme — passthrough, port-split still applies.
            ['example.com:9000',                'example.com'],
            // IPv4 with port.
            ['https://192.168.1.10:8080',       '192.168.1.10'],
            // Just a bare host.
            ['localhost',                       'localhost'],
        ];

        cases.forEach(([host, expected]) => {
            const url = host === null
                ? '/Core/domain'
                : `/Core/domain?hostOverride=${encodeURIComponent(host)}`;
            cy.request(url).then((res) => {
                expect(res.body, `host=${host ?? '(default)'}`).to.eq(expected);
            });
        });
    });

    // Request::getReadableParameter() splits the slug on '-': the last chunk
    // becomes `id`, the preceding chunks (re-joined with '-') become `text`.
    // Each shape gets its own it so a single mismatch is named clearly.

    it('getReadableParameter(0) — typical SEO slug splits id off the tail', () => {
        cy.request('/Core/readable/this-is-some-text-64').then((res) => {
            expect(JSON.parse(res.body)).to.deep.equal({ id: '64', text: 'this-is-some-text' });
        });
    });

    it('getReadableParameter(0) — two-segment slug: id is the trailing token', () => {
        cy.request('/Core/readable/abc-def').then((res) => {
            expect(JSON.parse(res.body)).to.deep.equal({ id: 'def', text: 'abc' });
        });
    });

    it('getReadableParameter(0) — single segment: the value becomes id, text empty', () => {
        cy.request('/Core/readable/standalone').then((res) => {
            expect(JSON.parse(res.body)).to.deep.equal({ id: 'standalone', text: '' });
        });
    });

    // Offset 1 skips the first URL parameter past controller/action — so the
    // slug being split is the *second* path segment after /Core/readable.
    it('getReadableParameter(1) — offset skips one parameter before splitting', () => {
        cy.request('/Core/readable/skip-me/foo-bar-7?offset=1').then((res) => {
            expect(JSON.parse(res.body)).to.deep.equal({ id: '7', text: 'foo-bar' });
        });
    });

    // Request::getBody() reads $this->input->body raw; Request::getJson()
    // decodes it with JSON_THROW_ON_ERROR. Verify round-trip on valid JSON
    // and that the throw surfaces on malformed input (probe catches it).
    it('getBody() + getJson() — round-trips valid JSON, surfaces JsonException on bad input', () => {
        const payload = { hello: 'world', n: 42, nested: { ok: true } };

        // cy.request auto-deserializes when the response looks like JSON;
        // accept either shape so the test is robust to that behavior.
        const parseBody = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

        cy.request({
            method: 'POST',
            url: '/Core/requestBody',
            body: payload, // cypress JSON-serializes objects
        }).then((res) => {
            const out = parseBody(res.body);
            expect(out.body).to.eq(JSON.stringify(payload));
            expect(out.json).to.deep.equal(payload);
            expect(out.jsonError).to.eq(null);
        });

        cy.request({
            method: 'POST',
            url: '/Core/requestBody',
            body: 'not-json{',
            headers: { 'Content-Type': 'text/plain' },
        }).then((res) => {
            const out = parseBody(res.body);
            expect(out.body).to.eq('not-json{');
            expect(out.json).to.eq(null);
            expect(out.jsonError, 'JsonException message captured').to.be.a('string').and.not.empty;
        });
    });
});