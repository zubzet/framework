// Background tasks run in their own container (the `worker` service), which
// executes `queue:work --workers=4` against the same database the application
// uses. These specs drive the system the way an application does: dispatch from
// a request, then poll, without ever touching the queue tables.

/** Polls an application status endpoint until the task reaches a final state. */
const waitForTask = (id, attemptsLeft = 40) => {
    return cy.request(`/tasks/status/${id}`).then((res) => {
        if (res.body.status === 'done' || res.body.status === 'failed') return res.body;

        if (attemptsLeft <= 0) {
            throw new Error(`Task ${id} stayed "${res.body.status}" and never finished`);
        }

        cy.wait(250);
        return waitForTask(id, attemptsLeft - 1);
    });
};

describe('Background tasks', () => {

    it('dispatches from a request and returns immediately', () => {
        const startedAt = Date.now();

        cy.request('/tasks/dispatch/5').then((res) => {
            // The point of the whole system: the request does not wait for the
            // work, it only hands back the id to poll with.
            expect(Date.now() - startedAt, 'dispatch stays within a request budget').to.be.lessThan(2000);
            expect(res.body.status).to.eq('pending');
            expect(res.body.id).to.be.a('number');
            expect(res.body.name).to.eq('Monthly report');
        });
    });

    it('runs the task in the worker container and stores its result', () => {
        cy.request('/tasks/dispatch/5').then((res) => {
            waitForTask(res.body.id).then((task) => {
                expect(task.status).to.eq('done');
                expect(task.progress, 'progress ends at 100').to.eq(100);
                expect(task.attempts, 'ran exactly once').to.eq(1);

                // Returned by ReportTask::handle().
                expect(task.result.rows).to.eq(5);
                expect(task.result.total).to.eq(15);

                // Proves it executed in a worker process, not in the request.
                expect(task.result.workerPid).to.be.a('number');

                expect(task.startedAt, 'startedAt is stamped').to.not.be.null;
                expect(task.finishedAt, 'finishedAt is stamped').to.not.be.null;
            });
        });
    });

    it('records a throwing task as failed with its message', () => {
        cy.request('/tasks/dispatch-failing').then((res) => {
            waitForTask(res.body.id).then((task) => {
                expect(task.status).to.eq('failed');
                expect(task.error).to.contain('expected e2e failure');
                expect(task.finishedAt).to.not.be.null;
            });
        });
    });

    it('spreads concurrent work across the pool, running each task once', () => {
        const ids = [];

        // Eight tasks against four workers. Each takes roughly 400ms, which
        // matters: with trivially short tasks the first worker drains the
        // whole queue before its siblings finish their poll interval, and
        // then nothing about the pool is being tested.
        Cypress._.times(8, () => {
            cy.request('/tasks/dispatch/8').then((res) => ids.push(res.body.id));
        });

        cy.then(() => {
            const results = [];
            ids.forEach((id) => waitForTask(id).then((task) => results.push(task)));

            cy.then(() => {
                expect(results).to.have.length(8);

                results.forEach((task) => {
                    expect(task.status, `task ${task.id}`).to.eq('done');

                    // A second worker claiming the same task would show here.
                    expect(task.attempts, `task ${task.id} claimed exclusively`).to.eq(1);
                });

                const workers = new Set(results.map((task) => task.result.workerPid));
                expect(workers.size, 'more than one worker took part').to.be.greaterThan(1);
            });
        });
    });

    it('keeps a delayed task out of reach until it is due', () => {
        cy.request('/tasks/dispatch-delayed').then((res) => {
            // Long enough for an idle pool to have claimed anything runnable.
            cy.wait(1500);

            cy.request(`/tasks/status/${res.body.id}`).then((status) => {
                expect(status.body.status, 'still waiting for its due time').to.eq('pending');
                expect(status.body.startedAt).to.be.null;
            });
        });
    });

    it('rejects an unknown task when it is dispatched, not when it runs', () => {
        cy.request('/tasks/dispatch-unknown').then((res) => {
            expect(res.body.error).to.contain('NoSuchTask');
            expect(res.body.error).to.contain('app/Tasks');
        });
    });

    it('refuses a payload it could not store, instead of storing an empty one', () => {
        cy.request('/tasks/dispatch-bad-payload').then((res) => {
            // Silently storing this would hand the task an empty payload and
            // run it with its own defaults.
            expect(res.body.error).to.contain('cannot be stored');
            expect(res.body.error).to.contain('UTF-8');
        });
    });

    it('counts what is still waiting on the queue', () => {
        cy.request('/tasks/pending').then((res) => {
            expect(res.body.pending).to.be.a('number');
        });
    });

    describe('the queue:work command', () => {

        it('drains one task and exits with --once', () => {
            cy.request('/tasks/dispatch-queue/manual').then((res) => {
                const id = res.body.id;

                // The running pool consumes "default" only, so this task waits
                // for a worker started for its own queue.
                cy.exec('docker exec application php index.php queue:work --once --queue=manual', {
                    failOnNonZeroExit: false,
                }).then((result) => {
                    expect(result.exitCode).to.eq(0);
                });

                cy.request(`/tasks/status/${id}`).then((status) => {
                    // FailingTask threw, and with the default of one attempt
                    // that is terminal.
                    expect(status.body.status).to.eq('failed');
                    expect(status.body.attempts).to.eq(1);
                });
            });
        });

        it('reports an empty queue without hanging', () => {
            cy.exec('docker exec application php index.php queue:work --once --queue=empty', {
                failOnNonZeroExit: false,
            }).then((result) => {
                expect(result.exitCode).to.eq(0);
                expect(result.stdout).to.contain('Worker stopped after 0 tasks');
            });
        });
    });

    describe('stopping the worker container', () => {

        // Restores the pool for the specs that follow, whatever happened here.
        afterEach(() => {
            cy.exec('docker start worker', { failOnNonZeroExit: false });
            cy.wait(8000);
        });

        it('lets a task in flight finish before the container goes down', () => {
            // Long enough that the stop lands mid-task.
            cy.request('/tasks/dispatch/60').then((res) => {
                const id = res.body.id;

                cy.wait(1500);
                cy.request(`/tasks/status/${id}`).then((mid) => {
                    expect(mid.body.status, 'the task is under way').to.eq('running');
                });

                // A killed worker would leave this task stuck at "running"
                // until its reservation expired.
                cy.exec('docker stop worker', { timeout: 90000 }).then((result) => {
                    expect(result.exitCode).to.eq(0);
                });

                cy.request(`/tasks/status/${id}`).then((after) => {
                    expect(after.body.status, 'finished during the drain').to.eq('done');
                    expect(after.body.progress).to.eq(100);
                    expect(after.body.result.rows).to.eq(60);
                });
            });
        });
    });

    describe('the framework status endpoint', () => {

        it('does not expose a task that has no owner', () => {
            cy.request('/tasks/dispatch/2').then((res) => {
                cy.request({
                    url: `/_zubzet/task/${res.body.id}`,
                    failOnStatusCode: false,
                }).then((status) => {
                    expect(status.status).to.eq(404);
                });
            });
        });

        it('serves the owner their own task, without its payload or result', () => {
            cy.loginAs('admin');

            cy.request('/tasks/dispatch-owned').then((res) => {
                expect(res.body.userId, 'dispatch captured the logged in user').to.not.be.null;

                cy.request(`/_zubzet/task/${res.body.id}`).then((status) => {
                    expect(status.status).to.eq(200);
                    expect(status.body.id).to.eq(res.body.id);
                    expect(status.body).to.have.property('progress');
                    expect(status.body).to.have.property('finished');

                    // Deliberately withheld: these can carry application internals.
                    expect(status.body).to.not.have.property('payload');
                    expect(status.body).to.not.have.property('result');
                    expect(status.body).to.not.have.property('error');
                });
            });
        });

        it('lists the current user their own tasks', () => {
            cy.loginAs('admin');

            cy.request('/tasks/dispatch-owned').then(() => {
                cy.request('/tasks/mine').then((res) => {
                    expect(res.body.count).to.be.greaterThan(0);
                    res.body.tasks.forEach((task) => {
                        expect(task.type).to.eq('ReportTask');
                    });
                });
            });
        });
    });
});
