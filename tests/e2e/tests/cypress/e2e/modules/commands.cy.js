// Convention console commands from app/Commands/ in userspace and modules:
// discovery, framework command listing, and userspace winning a command-name
// collision with a module (Symfony resolves names last-add-wins, the registry
// adds in reverse precedence order).

describe('Module and userspace commands', () => {
    const PHP = 'docker exec application php index.php';

    before(() => {
        cy.dbSeed();
    });

    it('runs a command shipped by a module', () => {
        cy.exec(`${PHP} guestbook:stats`).then(({ stdout }) => {
            const match = stdout.match(/guestbook-entries:(\d+)/);
            expect(match, 'guestbook-entries output').to.not.be.null;
            expect(Number(match[1])).to.be.at.least(3);
        });
    });

    it('runs a command shipped by the application', () => {
        cy.exec(`${PHP} qa:hello`).then(({ stdout }) => {
            expect(stdout).to.include('hello-from-userspace');
        });
    });

    it('lets the application override a module command of the same name', () => {
        // AppGuestbookGreetCommand (app) and GuestbookGreetCommand (module)
        // both register guestbook:greet; the userspace copy must win.
        cy.exec(`${PHP} guestbook:greet`).then(({ stdout }) => {
            expect(stdout).to.include('greet-from-app');
            expect(stdout).to.not.include('greet-from-module');
        });
    });

    it('lists convention commands next to the framework ones', () => {
        cy.exec(`${PHP} list`).then(({ stdout }) => {
            expect(stdout).to.include('guestbook:stats');
            expect(stdout).to.include('qa:hello');
            expect(stdout).to.include('db:migrate');
        });
    });
});
