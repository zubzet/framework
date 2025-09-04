describe('Form Date Validation', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Validation File Normal Small PDF', () => {
        cy.visit("/Form/validationFile");

        cy.fixture('TestFile_Small.pdf', 'base64').then(fileContent => {
            const blob = Cypress.Blob.base64StringToBlob(fileContent);
            const file = new File([blob], 'TestFile_Small.pdf', { type: 'application/json' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
                input[0].files = dataTransfer.files;
                cy.wrap(input).trigger('change', { force: true });
            });

            cy.get('button').click();
        });

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Small.pdf");
    });

    it('Validation File Normal Small TXT', () => {
        cy.visit("/Form/validationFile");

        cy.fixture('TestFile_Small.txt', 'base64').then(fileContent => {
            const blob = Cypress.Blob.base64StringToBlob(fileContent);
            const file = new File([blob], 'TestFile_Small.txt', { type: 'application/json' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
                input[0].files = dataTransfer.files;
                cy.wrap(input).trigger('change', { force: true });
            });

            cy.get('button').click();
        });

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Small.txt").should("not.exist");
    });

    it('Validation File Normal Big PDF', () => {
        cy.visit("/Form/validationFile");

        cy.fixture('TestFile_Big.pdf', 'base64').then(fileContent => {
            const blob = Cypress.Blob.base64StringToBlob(fileContent);
            const file = new File([blob], 'TestFile_Big.pdf', { type: 'application/json' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
                input[0].files = dataTransfer.files;
                cy.wrap(input).trigger('change', { force: true });
            });

            cy.get('button').click();
        });

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Big.pdf").should("not.exist");
    });

    it('Validation File Normal Big TXT', () => {
        cy.visit("/Form/validationFile");

        cy.fixture('TestFile_Big.txt', 'base64').then(fileContent => {
            const blob = Cypress.Blob.base64StringToBlob(fileContent);
            const file = new File([blob], 'TestFile_Big.txt', { type: 'application/json' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
                input[0].files = dataTransfer.files;
                cy.wrap(input).trigger('change', { force: true });
            });

            cy.get('button').click();
        });

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Big.txt").should("not.exist");
    });

    //

    it('Validation File Form Small PDF', () => {
        cy.visit("/Form/validationFile/form");

        cy.fixture('TestFile_Small_1.pdf', 'base64').then(fileContent => {
            const blob = Cypress.Blob.base64StringToBlob(fileContent);
            const file = new File([blob], 'TestFile_Small_1.pdf', { type: 'application/json' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
                input[0].files = dataTransfer.files;
                cy.wrap(input).trigger('change', { force: true });
            });

            cy.get('button').click();
        });

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Small_1.pdf");
    });

    it.skip('Validation File Form Small TXT', () => {
        cy.visit("/Form/validationFile/form");

        cy.fixture('TestFile_Small_1.txt', 'base64').then(fileContent => {
            const blob = Cypress.Blob.base64StringToBlob(fileContent);
            const file = new File([blob], 'TestFile_Small_1.txt', { type: 'application/json' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
                input[0].files = dataTransfer.files;
                cy.wrap(input).trigger('change', { force: true });
            });

            cy.get('button').click();
        });

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Small_1.txt").should("not.exist");
    });

    it('Validation File Form Big PDF', () => {
        cy.visit("/Form/validationFile/form");

        cy.fixture('TestFile_Big_1.pdf', 'base64').then(fileContent => {
            const blob = Cypress.Blob.base64StringToBlob(fileContent);
            const file = new File([blob], 'TestFile_Big_1.pdf', { type: 'application/json' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
                input[0].files = dataTransfer.files;
                cy.wrap(input).trigger('change', { force: true });
            });

            cy.get('button').click();
        });

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Big_1.pdf").should("not.exist");
    });

    it('Validation File Form Big TXT', () => {
        cy.visit("/Form/validationFile/form");

        cy.fixture('TestFile_Big_1.txt', 'base64').then(fileContent => {
            const blob = Cypress.Blob.base64StringToBlob(fileContent);
            const file = new File([blob], 'TestFile_Big_1.txt', { type: 'application/json' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            cy.get('input[name="file"]').then(input => {
                input[0].files = dataTransfer.files;
                cy.wrap(input).trigger('change', { force: true });
            });

            cy.get('button').click();
        });

        cy.visit("/Form/validationFile");
        cy.contains("TestFile_Big_1.txt").should("not.exist");
    });
});
