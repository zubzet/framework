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
                if (expected !== undefined) {
                    expect(res.body, JSON.stringify(headers)).to.eq(expected);
                } else {
                    expect(res.body, JSON.stringify(headers)).to.match(expectedMatch);
                }
            });
        });
    });
});