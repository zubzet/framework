// Direct-render coverage for views that ship in
// src/IncludedComponents/views/*. The test app deliberately overrides only
// `login.php` (the view) and `layout/default_layout.php` + `layout/mail_layout.php`
// (the layouts) to keep the override mechanism exercised; every other
// framework view falls through to the framework copy on the natural request
// paths. This spec renders the framework versions directly via
// FrameworkViewProbeController so their bodies are exercised end-to-end -
// and so any English-text regression in those bundled files is caught.

describe('Framework views - direct render', () => {

    describe('login.php (framework)', () => {
        it('renders the English login form', () => {
            cy.request('/FrameworkViewProbe/login').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Login');
                expect(res.body).to.include('Sign in');
                expect(res.body).to.include('Forgot Password?');
                expect(res.body).to.include("Don't have a account?");
                expect(res.body).to.include('id="username"');
                expect(res.body).to.include('id="password"');
            });
        });
    });

    describe('login_signup.php', () => {
        it('renders the English signup form', () => {
            cy.request('/FrameworkViewProbe/loginSignup').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Sign up');
                expect(res.body).to.include('Sign Up');
                expect(res.body).to.include('Login?');
                expect(res.body).to.include('id="password-confirm"');
            });
        });
    });

    describe('login_forgotpassword.php', () => {
        it('renders the English forgot-password form', () => {
            cy.request('/FrameworkViewProbe/loginForgotPassword').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Forgot password');
                expect(res.body).to.include('Send me an email');
                expect(res.body).to.include('Back to the Login');
                expect(res.body).to.include('id="usernameemail"');
            });
        });
    });

    describe('login_reset.php', () => {
        it('renders the password-reset form with the match-check script', () => {
            cy.request('/FrameworkViewProbe/loginReset').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Password reset');
                expect(res.body).to.include("Passwords don't match!");
                expect(res.body).to.include('id="input-password"');
                expect(res.body).to.include('id="input-password-repeat"');
                expect(res.body).to.include('id="button-reset"');
            });
        });
    });

    describe('login_verify.php branches', () => {
        it('success=true: shows the verified banner', () => {
            cy.request('/FrameworkViewProbe/loginVerifySuccess').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Your email was verified');
                expect(res.body).to.include('To the login');
                // The resend prompt must NOT appear on the success branch.
                expect(res.body).to.not.include('You are missing the verification mail');
            });
        });

        it('success=false: shows the resend prompt + form', () => {
            cy.request('/FrameworkViewProbe/loginVerifyFailure').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('You are missing the verification mail');
                expect(res.body).to.include('name="email"');
                expect(res.body).to.include('To the login');
                // The success line must NOT appear on the failure branch.
                expect(res.body).to.not.include('Your email was verified');
            });
        });
    });

    describe('login_verify_wait.php', () => {
        it('renders the post-resend wait message', () => {
            cy.request('/FrameworkViewProbe/loginVerifyWait').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('An email was sent to you');
                expect(res.body).to.include('Please check your inbox');
            });
        });
    });

    describe('email_verify.php', () => {
        it('renders the "click to verify" mail body with the supplied URL', () => {
            cy.request('/FrameworkViewProbe/emailVerify').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Thank you for signing up!');
                expect(res.body).to.include('Click this link to verify your email');
                expect(res.body).to.include('http://probe.example/verify-link');
            });
        });
    });

    describe('layouts (framework)', () => {

        it('default_layout.php wraps the body in a full HTML page (no test-app marker)', () => {
            cy.request('/FrameworkViewProbe/layoutDefault').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.match(/<!doctype html>/i);
                expect(res.body).to.match(/<html[^>]*>/i);
                expect(res.body).to.match(/<head\b/i);
                expect(res.body).to.match(/<body\b/i);
                // Body view content present.
                expect(res.body).to.include('FrameworkLayoutProbe');
                // Test-app override marker must NOT be present - proves
                // we hit the framework version, not the override.
                expect(res.body).to.not.include('ZubZet QA Suite');
            });
        });

        it('min_layout.php emits the configured <title> and wraps the body', () => {
            cy.request('/FrameworkViewProbe/layoutMin').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.match(/<html[^>]*>/i);
                expect(res.body).to.match(/<head\b/i);
                expect(res.body).to.include('FrameworkLayoutProbe');
            });
        });

        it('empty.php emits the body with no surrounding chrome', () => {
            cy.request('/FrameworkViewProbe/layoutEmpty').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('FrameworkLayoutProbe');
                expect(res.body).to.not.match(/<html\b/i);
                expect(res.body).to.not.match(/<head\b/i);
                expect(res.body).to.not.match(/<body\b/i);
                expect(res.body).to.not.match(/<!doctype/i);
            });
        });

        it('mail_layout.php emits the body with no surrounding chrome (no test-app h2)', () => {
            cy.request('/FrameworkViewProbe/layoutMail').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('FrameworkLayoutProbe');
                expect(res.body).to.not.include('<h2>mail_layout.php</h2>');
                expect(res.body).to.not.match(/<html\b/i);
            });
        });
    });
});
