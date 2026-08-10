// Round-trip tests for Response::sendEmail() and Response::sendEmailToUser()
// using the test app's RenderingController. Both actions render
// app/Views/rendering/testmail.php inside app/Views/rendering/mail_layout.php.
// smtp4dev catches the outgoing mail; we assert headers and body via its API.

describe('Rendering - Email', () => {
    before(() => {
        cy.dbSeed();
    });

    const fetchLatestMail = () =>
        cy.request('http://localhost:3300/api/messages').then((res) => {
            const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
            return body.results[0];
        });

    // smtp4dev exposes the email parts indexed; part/2 is the HTML body for
    // multipart messages (part/1 is the plain-text alternative).
    const fetchMailHtml = (id) =>
        cy.request(`http://localhost:3300/api/messages/${id}/part/2/source`)
            .then((res) => res.body);

    it('sendEmail() - renders document inside layout and delivers to the recipient', () => {
        cy.request('/Rendering/mail');

        fetchLatestMail().then((mail) => {
            expect(mail.subject).to.equal('TestEmail');
            expect(mail.to[0]).to.equal('admin@zierhut-it.de');
            expect(mail.from).to.equal('no-reply@zubzet.com');

            fetchMailHtml(mail.id).then((html) => {
                expect(html).to.include('TestValue');             // option passed to view
                expect(html).to.include('This is an test email'); // testmail.php body
                expect(html).to.include('Custom Layout Template'); // mail_layout.php wrapper
            });
        });
    });

    it('sendEmailToUser() - resolves the user, picks their language, delivers', () => {
        cy.request('/Rendering/mailuser');

        fetchLatestMail().then((mail) => {
            expect(mail.subject).to.equal('TestUserEmail');
            expect(mail.to[0]).to.equal('admin@zierhut-it.de'); // user 1
            expect(mail.from).to.equal('no-reply@zubzet.com');

            fetchMailHtml(mail.id).then((html) => {
                expect(html).to.include('TestUserValue');
                expect(html).to.include('Custom Layout Template');
            });
        });
    });
});
