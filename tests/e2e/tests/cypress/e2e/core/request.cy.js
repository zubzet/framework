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
});