import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';

const password = 'FlowTest!2026';
const runId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
const candidateEmail = `interview-${runId}@example.test`;
const resumeTitle = 'Interview E2E Resume';
const finalEvaluation = [
    '## Overall Assessment',
    'Strong, relevant answers throughout the AI interview.',
    '',
    '## Strengths',
    '- Clear examples',
    '- Concise communication',
    '',
    '## Areas to Improve',
    '- Add measurable outcomes',
    '',
    '## Recommendation',
    'Practice one more technical AI interview.',
].join('\n');

function persistCompletedResult(sessionId: number): void {
    const php = `
        $session = App\\Models\\InterviewSession::findOrFail(${sessionId});
        $conversationId = (string) Illuminate\\Support\\Str::uuid();
        $now = now();
        Illuminate\\Support\\Facades\\DB::table('agent_conversations')->insert([
            'id' => $conversationId,
            'user_id' => $session->user_id,
            'title' => 'Interview E2E result',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Illuminate\\Support\\Facades\\DB::table('agent_conversation_messages')->insert([
            'id' => (string) Illuminate\\Support\\Str::uuid(),
            'conversation_id' => $conversationId,
            'user_id' => $session->user_id,
            'agent' => 'App\\\\Ai\\\\Agents\\\\InterviewAgent',
            'role' => 'assistant',
            'content' => ${JSON.stringify(finalEvaluation)},
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $session->update(['conversation_id' => $conversationId, 'status' => 'completed']);
    `;

    execFileSync('php', ['artisan', 'tinker', '--execute', php], {
        cwd: process.cwd(),
        stdio: 'pipe',
    });
}

function persistUpcomingInterview(email: string): void {
    const php = `
        $candidate = App\\Models\\User::where('email', ${JSON.stringify(email)})->firstOrFail();
        $resume = $candidate->resumes()->firstOrFail();
        $employer = App\\Models\\User::factory()->employer()->create();
        $job = App\\Models\\WorkJob::create([
            'user_id' => $employer->id,
            'title' => 'Future Platform Engineer',
            'company' => 'Flow Labs',
            'description' => 'Build reliable platforms.',
            'contacts' => 'jobs@flow.test',
            'location' => 'Remote',
            'technologies' => ['PHP'],
        ]);
        $application = App\\Models\\UserWorkJobApplication::create([
            'user_id' => $candidate->id,
            'work_job_id' => $job->id,
            'resume_id' => $resume->id,
            'status' => App\\Enums\\ApplicationStatus::Applied,
        ]);
        App\\Models\\InterviewSession::create([
            'user_id' => $candidate->id,
            'resume_id' => $resume->id,
            'work_job_id' => $job->id,
            'application_id' => $application->id,
            'employer_id' => $employer->id,
            'type' => 'job_interview',
            'complexity' => 'standard',
            'mode' => 'scheduled',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'interview_format' => 'video',
        ]);
    `;

    execFileSync('php', ['artisan', 'tinker', '--execute', php], {
        cwd: process.cwd(),
        stdio: 'pipe',
    });
}

test('candidate can prepare, recover, complete an AI interview, and manage history', async ({
    page,
}, testInfo) => {
    test.setTimeout(60000);
    await page.goto('/register?type=candidate');
    await page.getByLabel('Email address').fill(candidateEmail);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByLabel('Confirm password').fill(password);
    await page.getByTestId('register-user-button').click();

    await expect(page).toHaveURL(/\/resumes(?:\?.*)?$/);
    await page.getByRole('button', { name: 'New Resume' }).click();
    await page.getByLabel('Resume title').fill(resumeTitle);
    await page.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(
        page.getByText(resumeTitle, { exact: true }).first(),
    ).toBeVisible();

    await page.goto('/interview-preparation');
    await expect(
        page.getByRole('heading', { name: 'Interview Center' }),
    ).toBeVisible();
    await expect(page.getByText('No upcoming interviews.')).toBeVisible();
    await expect(
        page.getByText('No completed AI interviews yet.'),
    ).toBeVisible();
    await page.getByTestId('interview-tab-history').click();
    await expect(
        page.getByText('No completed AI interviews yet.'),
    ).toBeVisible();

    persistUpcomingInterview(candidateEmail);
    await page.goto('/interview-preparation');
    await page.getByTestId('interview-resume-select').click();
    await page.getByRole('option', { name: resumeTitle }).click();
    await page.getByTestId('interview-job-select').click();
    await page
        .getByRole('option', { name: /Future Platform Engineer/ })
        .click();
    const upcomingInterview = page
        .getByTestId(/^upcoming-interview-/)
        .filter({ hasText: 'Future Platform Engineer' });
    await upcomingInterview.getByRole('button', { name: 'Prepare' }).click();
    await expect(page.getByTestId('interview-job-select')).toContainText(
        'Future Platform Engineer',
    );
    await page.getByTestId('interview-type-select').click();
    await page.getByRole('option', { name: 'Technical' }).click();
    await page.getByTestId('interview-difficulty-select').click();
    await page.getByRole('option', { name: 'Intermediate' }).click();
    await page.getByTestId('interview-mode-voice').click();
    await expect(page.getByTestId('interview-mode-voice')).toHaveAttribute(
        'aria-pressed',
        'true',
    );
    await page.getByTestId('interview-mode-text').click();
    await expect(page.getByTestId('interview-mode-text')).toHaveAttribute(
        'aria-pressed',
        'true',
    );
    await expect(
        page.getByRole('button', { name: 'Prepare with AI' }),
    ).toBeVisible();
    await expect(
        page.getByRole('button', { name: 'Start AI Interview' }),
    ).toBeVisible();
    await page.setViewportSize({ width: 1536, height: 1024 });
    await page.screenshot({
        path: testInfo.outputPath('interview-center-desktop.png'),
        fullPage: true,
    });
    await page.setViewportSize({ width: 390, height: 844 });
    expect(
        await page.evaluate(
            () =>
                document.documentElement.scrollWidth <=
                document.documentElement.clientWidth,
        ),
    ).toBe(true);
    await page.screenshot({
        path: testInfo.outputPath('interview-center-mobile.png'),
        fullPage: true,
    });
    await page.setViewportSize({ width: 1536, height: 1024 });

    await page.route('**/interview-prep/guidance', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                guidance: [
                    '## Preparation Plan',
                    '- Review your strongest technical project.',
                    '- Prepare concise STAR examples.',
                    '- Connect your experience to the role.',
                ].join('\n'),
            }),
        });
    });

    await page.getByRole('button', { name: 'Prepare with AI' }).click();
    await expect(page).toHaveURL(/\/interview-prep\?/);
    await expect(page.getByText(resumeTitle, { exact: true })).toBeVisible();
    await expect(
        page.getByText('Future Platform Engineer', { exact: true }),
    ).toBeVisible();
    await expect(page.getByText('Technical · Intermediate')).toBeVisible();
    await expect(
        page.getByText(/Formal scoring happens only after/),
    ).toBeVisible();

    await page.getByRole('button', { name: 'Prepare with AI' }).click();
    await expect(
        page.getByRole('heading', { name: 'Preparation Plan' }),
    ).toBeVisible();
    await expect(
        page.getByText('Review your strongest technical project.'),
    ).toBeVisible();
    await expect(page.getByText(/Question 1 of/)).toHaveCount(0);

    await page.goto('/interview-preparation');
    await page.getByTestId('interview-job-select').click();
    await page
        .getByRole('option', { name: /Future Platform Engineer/ })
        .click();
    await page.getByTestId('interview-type-select').click();
    await page.getByRole('option', { name: 'Technical' }).click();
    await page.getByTestId('interview-difficulty-select').click();
    await page.getByRole('option', { name: 'Intermediate' }).click();

    let firstStartAttempt = true;
    let questionNumber = 0;
    await page.route('**/interview-sessions/*/message', async (route) => {
        const payload = route.request().postDataJSON() as {
            intent: 'start' | 'answer';
        };

        await new Promise((resolve) => setTimeout(resolve, 250));

        if (payload.intent === 'start' && firstStartAttempt) {
            firstStartAttempt = false;
            await route.fulfill({
                status: 422,
                contentType: 'application/json',
                body: JSON.stringify({
                    message: "We couldn't prepare the interview question.",
                }),
            });

            return;
        }

        if (payload.intent === 'start') {
            questionNumber = 1;
        } else if (questionNumber < 6) {
            questionNumber += 1;
        } else {
            const sessionId = route
                .request()
                .url()
                .match(/interview-sessions\/(\d+)\/message/)?.[1];
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    message: null,
                    question_number: 6,
                    total_questions: 6,
                    session_status: 'completed',
                    results_url: `/interview-sessions/${sessionId}/results`,
                }),
            });

            return;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                message: {
                    role: 'assistant',
                    content: `Technical question ${questionNumber}?`,
                },
                question_number: questionNumber,
                total_questions: 6,
                session_status: 'in_progress',
            }),
        });
    });

    await page.getByRole('button', { name: 'Start AI Interview' }).click();
    await expect(page).toHaveURL(/\/interview-sessions\/\d+$/);
    await expect(
        page.getByText('The AI is preparing your first interview question.'),
    ).toBeVisible();
    await expect(
        page.getByText("We couldn't prepare the interview question."),
    ).toBeVisible();
    await expect(page.getByPlaceholder('Type your answer...')).toHaveCount(0);

    await page.getByRole('button', { name: 'Try Again' }).click();
    await expect(
        page.getByText('The AI is preparing your first interview question.'),
    ).toBeVisible();
    await expect(page.getByText('Technical question 1?')).toBeVisible();
    await expect(page.getByText('Question 1 of 6')).toBeVisible();
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(
        'Intermediate Technical AI Interview',
    );
    await expect(page.getByText(/Interview Interview/)).toHaveCount(0);
    await expect(page.getByText(/hint|suggested answer|coaching/i)).toHaveCount(
        0,
    );

    for (let answerNumber = 1; answerNumber <= 6; answerNumber += 1) {
        await page
            .getByPlaceholder('Type your answer...')
            .fill(`Candidate answer ${answerNumber}`);
        await page
            .getByRole('button')
            .filter({ has: page.locator('svg') })
            .last()
            .click();

        if (answerNumber < 6) {
            await expect(
                page.getByText(`Technical question ${answerNumber + 1}?`),
            ).toBeVisible();
            await expect(
                page.getByText(`Question ${answerNumber + 1} of 6`),
            ).toBeVisible();
        }
    }

    await expect(page.getByText('Completed', { exact: true })).toBeVisible();
    await expect(page.getByText(/preparing/i)).toHaveCount(0);
    await expect(page.getByPlaceholder('Type your answer...')).toHaveCount(0);
    const resultsLink = page.getByRole('link', {
        name: 'View Interview Results',
    });
    await expect(resultsLink).toBeVisible();

    const sessionId = Number(
        page.url().match(/interview-sessions\/(\d+)$/)?.[1],
    );
    expect(sessionId).toBeGreaterThan(0);
    persistCompletedResult(sessionId);

    await resultsLink.click();
    await expect(page).toHaveURL(
        new RegExp(`/interview-sessions/${sessionId}/results$`),
    );
    await expect(
        page.getByRole('heading', { name: 'Interview Results' }),
    ).toBeVisible();
    await expect(
        page.getByRole('heading', { name: 'Overall Assessment' }),
    ).toBeVisible();
    await expect(
        page.getByRole('heading', { name: 'Strengths' }),
    ).toBeVisible();
    await expect(
        page.getByRole('heading', { name: 'Areas to Improve' }),
    ).toBeVisible();
    await expect(
        page.getByRole('heading', { name: 'Recommendation' }),
    ).toBeVisible();

    await page.goto('/interview-preparation');
    await page.getByTestId('interview-tab-history').click();
    const historyRow = page.getByTestId(`interview-history-${sessionId}`);
    await expect(historyRow).toBeVisible();
    await expect(
        historyRow.getByRole('link', { name: 'View Results' }),
    ).toBeVisible();

    await historyRow
        .getByRole('button', { name: `Delete interview ${sessionId}` })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Delete this interview?' }),
    ).toBeVisible();
    await page.getByRole('button', { name: 'Cancel' }).click();
    await expect(historyRow).toBeVisible();

    await historyRow
        .getByRole('button', { name: `Delete interview ${sessionId}` })
        .click();
    await page.getByRole('button', { name: 'Delete Interview' }).click();
    await expect(historyRow).toHaveCount(0);
});
