describe('Mail System', () => {

    it('should send a simple static email', () => {
        cy.visit("/core/sendemail-static");

        cy.request('http://localhost:3300/api/messages').then((response) => {
            const responseBody = typeof response.body === 'string' 
                ? JSON.parse(response.body) 
                : response.body;

            const newestEmail = responseBody.results[0];
            const emailId = newestEmail.id;
            expect(newestEmail.subject).to.equal('This is a Test Email Static');
            expect(newestEmail.to[0]).to.equal('test@zierhut-it.de');
            expect(newestEmail.from).to.equal('no-reply@zubzet.com');

            cy.request(`http://localhost:3300/api/messages/${emailId}/part/2/source`).then((htmlResponse) => {
                expect(htmlResponse.body).to.include('<h2>Test EMail Static</h2>');
            });
        });
    });

    it('should send a simple static email with mail layout', () => {
        cy.visit("/core/sendemail-static-mail-layout");

        cy.request('http://localhost:3300/api/messages').then((response) => {
            const responseBody = typeof response.body === 'string' 
                ? JSON.parse(response.body) 
                : response.body;

            const newestEmail = responseBody.results[0];
            const emailId = newestEmail.id;
            expect(newestEmail.subject).to.equal('This is a Test Email Static');
            expect(newestEmail.to[0]).to.equal('test@zierhut-it.de');
            expect(newestEmail.from).to.equal('no-reply@zubzet.com');

            cy.request(`http://localhost:3300/api/messages/${emailId}/part/2/source`).then((htmlResponse) => {
                expect(htmlResponse.body).to.include('<h2>Test EMail Static</h2>');
                expect(htmlResponse.body).to.include('<h2>mail_layout.php</h2>');
            });
        });
    });

    it('should send a simple static email with mail layout', () => {
        cy.visit("/core/sendemail-static-mail-layout-path");

        cy.request('http://localhost:3300/api/messages').then((response) => {
            const responseBody = typeof response.body === 'string' 
                ? JSON.parse(response.body) 
                : response.body;

            const newestEmail = responseBody.results[0];
            const emailId = newestEmail.id;
            expect(newestEmail.subject).to.equal('This is a Test Email Static');
            expect(newestEmail.to[0]).to.equal('test@zierhut-it.de');
            expect(newestEmail.from).to.equal('no-reply@zubzet.com');

            cy.request(`http://localhost:3300/api/messages/${emailId}/part/2/source`).then((htmlResponse) => {
                expect(htmlResponse.body).to.include('<h2>Test EMail Static</h2>');
                expect(htmlResponse.body).to.include('<h2>mail_layout.php</h2>');
            });
        });
    });

    it("should send a simple dynamic email", () => {
        cy.visit("/core/sendemail-dynamic");

        cy.request('http://localhost:3300/api/messages').then((response) => {
            const responseBody = typeof response.body === 'string' 
                ? JSON.parse(response.body) 
                : response.body;

            expect(responseBody.results.length).to.be.greaterThan(0);

            const newestEmail = responseBody.results[0];
            expect(newestEmail.subject).to.equal('This is a Test Email Dynamic');
            expect(newestEmail.to[0]).to.equal('test@zierhut-it.de');
            expect(newestEmail.from).to.equal('no-reply@zubzet.com');

            cy.request(`http://localhost:3300/api/messages/${newestEmail.id}/part/2/source`).then((htmlResponse) => {
                expect(htmlResponse.body).to.include('<h2>Test EMail Dynamic</h2>');
                expect(htmlResponse.body).to.include('<h2>TEST-EMAIL-LAYOUT</h2>');
                expect(htmlResponse.body).to.include('<p>Test Data 1</p>');
                expect(htmlResponse.body).to.include('<p>Test Data 2</p>');
            });
        });
    });


    it('should send a simple static email to a user', () => {
        cy.visit("/core/sendemailtouser-static");

        cy.request('http://localhost:3300/api/messages').then((response) => {
            const responseBody = typeof response.body === 'string' 
                ? JSON.parse(response.body) 
                : response.body;

            const newestEmail = responseBody.results[0];
            const emailId = newestEmail.id;

            expect(newestEmail.subject).to.equal('This is a Test Email Static');
            expect(newestEmail.to[0]).to.equal('admin@zierhut-it.de');
            expect(newestEmail.from).to.equal('no-reply@zubzet.com');

            cy.request(`http://localhost:3300/api/messages/${emailId}/part/2/source`).then((htmlResponse) => {
                expect(htmlResponse.body).to.include('<h2>Test EMail Static</h2>');
            });
        });
    });

    it("should send a simple dynamic email to a user", () => {
        cy.visit("/core/sendemailtouser-dynamic");

        cy.request('http://localhost:3300/api/messages').then((response) => {
            const responseBody = typeof response.body === 'string' 
                ? JSON.parse(response.body) 
                : response.body;

            expect(responseBody.results.length).to.be.greaterThan(0);

            const newestEmail = responseBody.results[0];
            expect(newestEmail.subject).to.equal('This is a Test Email Dynamic');
            expect(newestEmail.to[0]).to.equal('admin@zierhut-it.de');
            expect(newestEmail.from).to.equal('no-reply@zubzet.com');

            cy.request(`http://localhost:3300/api/messages/${newestEmail.id}/part/2/source`).then((htmlResponse) => {
                expect(htmlResponse.body).to.include('<h2>Test EMail Dynamic</h2>');
                expect(htmlResponse.body).to.include('<h2>TEST-EMAIL-LAYOUT</h2>');
                expect(htmlResponse.body).to.include('<p>Test Data 1</p>');
                expect(htmlResponse.body).to.include('<p>Test Data 2</p>');
            });
        });
    });
});