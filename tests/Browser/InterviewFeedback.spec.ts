import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

test.use({
    launchOptions: { executablePath: process.env.JOBFLOW_CHROMIUM_PATH },
});
test.beforeEach(() => {
    test.skip(
        process.env.JOBFLOW_FEEDBACK_E2E !== '1',
        'Requires the isolated testing-only feedback router.',
    );
});

async function setup(page: Page): Promise<void> {
    await page.goto('/register?type=candidate');
    await page
        .getByLabel('Email address')
        .fill(
            `feedback-${Date.now()}-${Math.random().toString(16).slice(2)}@example.test`,
        );
    await page
        .getByLabel('Password', { exact: true })
        .fill('FeedbackTest!2026');
    await page.getByLabel('Confirm password').fill('FeedbackTest!2026');
    await page.getByTestId('register-user-button').click();
    await expect(page).toHaveURL(/\/resumes/);
    await page.getByRole('button', { name: 'New Resume' }).click();
    await page.getByLabel('Resume title').fill('Feedback E2E Resume');
    await page.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(
        page.getByText('Feedback E2E Resume', { exact: true }).first(),
    ).toBeVisible();
    await page.goto('/interview-preparation');
}

function sessionState(id: number) {
    const php = `$s=App\\Models\\InterviewSession::findOrFail(${id}); echo json_encode(['status'=>$s->status,'feedback'=>$s->feedback_status,'conversation'=>$s->conversation_id,'answers'=>Illuminate\\Support\\Facades\\DB::table('agent_conversation_messages')->where('conversation_id',$s->conversation_id)->where('role','user')->where('content','like','Browser answer %')->pluck('content')]);`;

    return JSON.parse(
        execFileSync('php', ['artisan', 'tinker', '--execute', php], {
            encoding: 'utf8',
        }).trim(),
    );
}

function evaluationCalls(conversation: string): number {
    return readFileSync('/tmp/jobflow-feedback-calls.jsonl', 'utf8')
        .trim()
        .split('\n')
        .filter(Boolean)
        .map((line) => JSON.parse(line))
        .filter((event) => event.conversation === conversation).length;
}

async function finishSixQuestions(page: Page): Promise<number> {
    await page.getByTestId('start-ai-interview').click();
    await expect(page).toHaveURL(/\/interview-sessions\/\d+$/);
    const id = Number(page.url().match(/interview-sessions\/(\d+)/)?.[1]);

    for (let number = 1; number <= 6; number++) {
        await expect(
            page.getByText(`Question ${number} of 6`, { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText(
                `Question ${number}: Describe a project decision and its outcome.`,
                { exact: true },
            ),
        ).toBeVisible();
        const answer = page.getByPlaceholder('Type your answer...');
        await answer.fill(
            `Browser answer ${number}: I compared alternatives and measured the outcome.`,
        );
        await answer.press('Enter');
    }

    await expect(
        page.getByText('Interview completed', { exact: true }),
    ).toBeVisible();
    await expect(
        page.getByText('Generating your feedback...', { exact: true }),
    ).toBeVisible();
    await expect(page.getByPlaceholder('Type your answer...')).toHaveCount(0);
    await expect(page.getByText('Preparing the next question...')).toHaveCount(
        0,
    );

    return id;
}

test('active Text and Voice notices, Continue and End keep modes independent', async ({
    page,
}) => {
    await setup(page);
    await page.getByTestId('start-ai-interview').click();
    await expect(
        page.getByText('Question 1 of 6', { exact: true }),
    ).toBeVisible();
    const textUrl = page.url();
    await page.goto('/interview-preparation');
    await expect(
        page.getByText('Text AI Interview already in progress'),
    ).toBeVisible();
    await page
        .getByRole('link', { name: 'Continue Interview', exact: true })
        .click();
    await expect(page).toHaveURL(textUrl);
    await expect(
        page.getByText('Question 1 of 6', { exact: true }),
    ).toBeVisible();
    await page.goto('/interview-preparation');
    await page.getByTestId('interview-mode-voice').click();
    await expect(page.getByTestId('active-interview-notice')).toHaveCount(0);
    await expect(page.getByTestId('start-ai-interview')).toBeEnabled();
    // Speech synthesis is outside this completion task; no provider/microphone calls.
    await page.route('**/interview-sessions/*/audio', (route) =>
        route.fulfill({
            status: 422,
            contentType: 'application/json',
            body: '{"message":"Audio not used in this completion test."}',
        }),
    );
    await page.getByTestId('start-ai-interview').click();
    await expect(
        page.getByText(
            'Question 1: Describe a project decision and its outcome.',
            { exact: true },
        ),
    ).toBeVisible();
    const voiceUrl = page.url();
    expect(voiceUrl).not.toBe(textUrl);
    await page.goto('/interview-preparation');
    await page.getByTestId('interview-mode-voice').click();
    await expect(
        page.getByText('Voice AI Interview already in progress'),
    ).toBeVisible();
    await page
        .getByRole('link', { name: 'Continue Interview', exact: true })
        .click();
    await expect(page).toHaveURL(voiceUrl);
    await page.goto('/interview-preparation');
    await page
        .getByRole('button', { name: 'End Interview', exact: true })
        .click();
    await expect(page.getByTestId('interview-resume-select')).toBeEnabled();
    await expect(page.getByTestId('start-ai-interview')).toBeEnabled();
    await expect(page.getByTestId('active-interview-notice')).toHaveCount(0);
    await page.getByTestId('interview-mode-voice').click();
    await expect(
        page.getByText('Voice AI Interview already in progress'),
    ).toBeVisible();
    await page
        .getByRole('button', { name: 'End Interview', exact: true })
        .click();
    await expect(page.getByTestId('start-ai-interview')).toBeEnabled();
    await expect(page.getByTestId('interview-resume-select')).toBeEnabled();
    await expect(page.getByTestId('active-interview-notice')).toHaveCount(0);
});

test('six answers complete before one feedback call and results render semantic Markdown', async ({
    page,
}) => {
    await setup(page);
    const id = await finishSixQuestions(page);
    await expect(page.getByText('Your feedback is ready.')).toBeVisible();
    const saved = sessionState(id);
    expect(saved.status).toBe('completed');
    expect(saved.answers).toHaveLength(6);
    expect(evaluationCalls(saved.conversation)).toBe(1);
    await page.getByRole('link', { name: 'View Interview Results' }).click();
    await expect(
        page.getByRole('heading', { name: 'Interview Results', exact: true }),
    ).toBeVisible();
    const result = page.getByTestId('interview-result');

    for (const title of [
        'Overall Assessment',
        'Strengths',
        'Areas to Improve',
        'Recommendation',
    ]) {
        await expect(
            result.getByRole('heading', { name: title, exact: true }),
        ).toBeVisible();
        const style = await result
            .getByRole('heading', { name: title, exact: true })
            .evaluate((node) => ({
                size: getComputedStyle(node).fontSize,
                weight: getComputedStyle(node).fontWeight,
            }));
        expect(style.size).toBe('18px');
        expect(Number(style.weight)).toBeGreaterThanOrEqual(700);
    }

    await expect(result.locator('ul')).toHaveCount(2);
    await expect(result.locator('li')).toHaveCount(3);
    expect(
        await result
            .locator('ul')
            .first()
            .evaluate((node) => getComputedStyle(node).listStyleType),
    ).toBe('disc');
    await page.reload();
    await expect(
        result.getByRole('heading', { name: 'Recommendation' }),
    ).toBeVisible();
    expect(evaluationCalls(saved.conversation)).toBe(1);

    for (const width of [1366, 390]) {
        await page.setViewportSize({ width, height: 900 });
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth <= window.innerWidth,
            ),
        ).toBe(true);
    }
});

test('failed evaluation keeps all answers and Retry Feedback succeeds without repeating questions', async ({
    page,
}) => {
    await setup(page);
    writeFileSync('/tmp/jobflow-feedback-fail-once', '1');
    const id = await finishSixQuestions(page);
    await expect(
        page.getByText(
            "Your interview was saved, but we couldn't generate feedback yet.",
        ),
    ).toBeVisible();
    const saved = sessionState(id);
    expect(saved.status).toBe('completed');
    expect(saved.feedback).toBe('failed');
    expect(saved.answers).toHaveLength(6);
    await page
        .getByRole('button', { name: 'Retry Feedback', exact: true })
        .click();
    await expect(
        page.getByText('Generating your feedback...', { exact: true }),
    ).toBeVisible();
    await expect(page.getByText('Your feedback is ready.')).toBeVisible();
    expect(sessionState(id).answers).toEqual(saved.answers);
    expect(evaluationCalls(saved.conversation)).toBe(2);
    await page.getByRole('link', { name: 'View Interview Results' }).click();
    await expect(
        page
            .getByTestId('interview-result')
            .getByRole('heading', { name: 'Recommendation' }),
    ).toBeVisible();
});
