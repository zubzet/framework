describe('Request', () => {
    before(() => {
        cy.dbSeed();
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