describe('Form Date Validation', () => {

    const files = [
        { name: 'TestFile_Big_1.pdf', size: 513 },
        { name: 'TestFile_Big.pdf',    size: 513 },
        { name: 'TestFile_Big_1.txt',  size: 513 },
        { name: 'TestFile_Big.txt',    size: 513 },
        { name: 'TestFile_Small_1.pdf', size: 1 },
        { name: 'TestFile_Small.pdf',   size: 1 },
        { name: 'TestFile_Small_1.txt', size: 1 },
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

    function uploadFile(fixtureName, mimeType) {
        return cy.readFile(`${dir}/${fixtureName}`, null).then(buf => {
            const blob = new Blob([buf], { type: mimeType });
            const file = new File([blob], fixtureName, { type: mimeType });

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
            input[0].files = dataTransfer.files;
            cy.wrap(input).trigger('change', { force: true });
            });
        });
    }

    it('Validation File Normal Small PDF', () => {
        cy.visit("/Form/validationFile");

        uploadFile("TestFile_Small.pdf", "application/pdf");
        cy.get('button').click();

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Small.pdf").should("exist");
    });

    it('Validation File Normal Small TXT', () => {
        cy.visit("/Form/validationFile");

        uploadFile("TestFile_Small.txt", "text/plain");
        cy.get('button').click();

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Small.txt").should("not.exist");
    });

    it('Validation File Normal Big PDF', () => {
        cy.visit("/Form/validationFile");

        uploadFile("TestFile_Big.pdf", "application/pdf");
        cy.get('button').click();

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Big.pdf").should("not.exist");
    });

    it('Validation File Normal Big TXT', () => {
        cy.visit("/Form/validationFile");

        uploadFile("TestFile_Big.txt", "text/plain");
        cy.get('button').click();

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Big.txt").should("not.exist");
    });

        // -- Form Cases --

    it('Validation File Form Small PDF', () => {
        cy.visit("/Form/validationFile/form");

        uploadFile("TestFile_Small_1.pdf", "application/pdf");
        cy.get('button').click();

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Small_1.pdf");
    });

    it('Validation File Form Small TXT', () => {
        cy.visit("/Form/validationFile/form");

        uploadFile("TestFile_Small_1.txt", "text/plain");
        cy.get('button').click();

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Small_1.txt").should("not.exist");
    });

    it('Validation File Form Big PDF', () => {
        cy.visit("/Form/validationFile/form");

        uploadFile("TestFile_Big_1.pdf", "application/pdf");
        cy.get('button').click();

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Big_1.pdf").should("not.exist");
    });

    it('Validation File Form Big TXT', () => {
        cy.visit("/Form/validationFile/form");

        uploadFile("TestFile_Big_1.txt", "text/plain");
        cy.get('button').click();

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Big_1.txt").should("not.exist");
    });
});
