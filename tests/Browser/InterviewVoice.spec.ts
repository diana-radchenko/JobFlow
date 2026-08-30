import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

// Default tests exercise real browser capture/MediaRecorder with controlled responses.
// Opt-in provider runs use prerecorded neutral speech, never an ambient microphone.
const realProviders = process.env.JOBFLOW_REAL_VOICE_BROWSER === '1';
test.use({
    launchOptions: {
        executablePath: process.env.JOBFLOW_CHROMIUM_PATH,
        args: [
            '--use-fake-ui-for-media-stream',
            '--use-fake-device-for-media-stream',
            '--autoplay-policy=no-user-gesture-required',
            ...(process.env.JOBFLOW_VOICE_WAV
                ? [
                      `--use-file-for-fake-audio-capture=${process.env.JOBFLOW_VOICE_WAV}`,
                  ]
                : []),
        ],
    },
});

async function setup(page: Page, existingText = false) {
    const email = `voice-${Date.now()}-${Math.random().toString(16).slice(2)}@example.test`;
    await page.goto('/register?type=candidate');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill('VoiceTest!2026');
    await page.getByLabel('Confirm password').fill('VoiceTest!2026');
    await page.getByTestId('register-user-button').click();
    await expect(page).toHaveURL(/\/resumes(?:\?.*)?$/);
    await page.getByRole('button', { name: 'New Resume' }).click();
    await page.getByLabel('Resume title').fill('Voice Validation Resume');
    await page.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(
        page.getByText('Voice Validation Resume', { exact: true }).first(),
    ).toBeVisible();

    if (existingText) {
        execFileSync(
            'php',
            [
                'artisan',
                'tinker',
                '--execute',
                `
            $user = App\\Models\\User::where('email', ${JSON.stringify(email)})->firstOrFail();
            App\\Models\\InterviewSession::create([
                'user_id' => $user->id, 'resume_id' => $user->resumes()->firstOrFail()->id,
                'type' => 'technical', 'complexity' => 'intermediate', 'mode' => 'text', 'status' => 'in_progress',
            ]);
        `,
            ],
            { cwd: process.cwd(), stdio: 'pipe' },
        );
    }

    await page.goto('/interview-preparation');
    await page.getByTestId('interview-mode-voice').click();
    await expect(page.getByTestId('interview-mode-voice')).toHaveAttribute(
        'aria-pressed',
        'true',
    );
}

async function record(page: Page, stopLabel: string) {
    await page
        .getByRole('button', { name: 'Start recording', exact: true })
        .click();
    await expect(
        page.getByRole('button', { name: stopLabel, exact: true }),
    ).toBeVisible();
    // Capture real chunks from MediaRecorder (fixture speech for opt-in runs).
    await page.waitForTimeout(realProviders ? 6000 : 1200);
    await page.getByRole('button', { name: stopLabel, exact: true }).click();
}

test('voice interview records, retries transcription, progresses and completes without changing Text mode', async ({
    page,
}, testInfo) => {
    test.skip(
        realProviders,
        'Controlled error cases run separately from real provider checks.',
    );
    await setup(page, true);
    const capabilities = await page.evaluate(() => ({
        secure: window.isSecureContext,
        microphone: typeof navigator.mediaDevices?.getUserMedia === 'function',
        recorder: typeof MediaRecorder !== 'undefined',
        mime: MediaRecorder.isTypeSupported('audio/webm;codecs=opus'),
    }));
    expect(capabilities).toEqual({
        secure: true,
        microphone: true,
        recorder: true,
        mime: true,
    });
    await testInfo.attach('browser-capabilities', {
        body: JSON.stringify(capabilities),
        contentType: 'application/json',
    });
    let question = 0;
    await page.route('**/interview-sessions/*/message', (route) => {
        const payload = route.request().postDataJSON();
        question = payload.intent === 'start' ? 1 : question + 1;

        return route.fulfill({
            json: {
                message:
                    question <= 6
                        ? {
                              role: 'assistant',
                              content: `Question ${question}: Describe your project.`,
                          }
                        : null,
                session_status: question <= 6 ? 'in_progress' : 'completed',
            },
        });
    });
    await page.route('**/interview-sessions/*/audio', (route) =>
        route.fulfill({
            status: 503,
            json: { message: 'Voice service temporarily unavailable.' },
        }),
    );
    let uploadAttempts = 0;
    await page.route('**/interview-sessions/*/transcribe', async (route) => {
        const request = route.request();
        expect(request.headers()['x-csrf-token']).toBeTruthy();
        expect(request.headers()['content-type']).toContain(
            'multipart/form-data; boundary=',
        );
        const body = request.postDataBuffer();
        expect(body?.length).toBeGreaterThan(100);
        expect(body?.toString()).toContain('filename="answer.webm"');
        await testInfo.attach(`recording-${++uploadAttempts}`, {
            body: JSON.stringify({ bytes: body?.length, csrfPresent: true }),
            contentType: 'application/json',
        });
        await route.fulfill(
            uploadAttempts === 1
                ? {
                      status: 503,
                      json: {
                          message:
                              'Transcription temporarily unavailable. Please try again.',
                      },
                  }
                : {
                      json: {
                          text: 'I built a reliable application with my team.',
                      },
                  },
        );
    });
    await page.getByRole('button', { name: /^Start AI Interview/ }).click();
    await expect(
        page.getByText('Question 1: Describe your project.'),
    ).toBeVisible();
    await expect(page.getByRole('alert')).toContainText(
        'Voice service temporarily unavailable',
    );
    await record(page, 'Stop recording and send answer');
    await expect(page.getByRole('alert')).toContainText(
        'Transcription temporarily unavailable',
    );
    await page.getByRole('button', { name: 'Try Again', exact: true }).click();
    await expect(
        page.getByText('Question 2: Describe your project.'),
    ).toBeVisible();

    for (let answer = 2; answer <= 6; answer++) {
        await page
            .getByLabel('Or type your answer')
            .fill(`Example answer ${answer}.`);
        await page
            .getByRole('button', { name: 'Send answer', exact: true })
            .click();

        if (answer < 6) {
            await expect(
                page.getByText(
                    `Question ${answer + 1}: Describe your project.`,
                ),
            ).toBeVisible();
        }
    }

    await expect(page.getByText('Completed', { exact: true })).toBeVisible();
    await expect(
        page.getByRole('button', { name: 'View Interview Results' }),
    ).toBeVisible();
    await expect(page.getByLabel('Or type your answer')).toHaveCount(0);
    await expect(page.getByText('Thinking', { exact: true })).toHaveCount(0);
    expect(uploadAttempts).toBe(2);
});

test('voice prep explains microphone denial and keeps coaching text fallback', async ({
    page,
}) => {
    test.skip(realProviders, 'Permission error is intentionally simulated.');
    await page.addInitScript(() => {
        navigator.mediaDevices.getUserMedia = async () => {
            throw new DOMException('Denied', 'NotAllowedError');
        };
    });
    await setup(page);
    await page.route('**/interview-prep/guidance', async (route) => {
        expect(route.request().postDataJSON()).toMatchObject({
            mode: 'live',
            practice_answer: 'A typed practice answer.',
        });
        await route.fulfill({
            json: {
                guidance: '## Coaching\nExplain the outcome of your example.',
            },
        });
    });
    await page.getByRole('button', { name: /^Prepare with AI/ }).click();
    await page
        .getByRole('button', { name: 'Start recording', exact: true })
        .click();
    await expect(page.getByRole('alert')).toContainText(
        'Microphone permission was denied',
    );
    await expect(page.getByRole('status')).toHaveText('Ready');
    await page
        .getByLabel('Transcript / text fallback')
        .fill('A typed practice answer.');
    await page.getByRole('button', { name: /^Prepare with AI/ }).click();
    await expect(page.getByRole('heading', { name: 'Coaching' })).toBeVisible();
    await expect(
        page.getByText(/Formal scoring happens only after/),
    ).toBeVisible();
});

test('real voice Prep and AI Interview use recorded speech, real endpoints and playable provider audio', async ({
    page,
}, testInfo) => {
    test.skip(
        !realProviders || !process.env.JOBFLOW_VOICE_WAV,
        'Requires opt-in test-only provider credentials and neutral WAV fixture.',
    );
    test.setTimeout(240_000);
    await setup(page);
    await page.getByRole('button', { name: /^Prepare with AI/ }).click();
    const prepTranscription = page.waitForResponse((response) =>
        response.url().endsWith('/interview-prep/transcribe'),
    );
    await record(page, 'Stop recording and get coaching');
    const prepResponse = await prepTranscription;
    expect(prepResponse.status()).toBe(200);
    expect((await prepResponse.json()).text.length).toBeGreaterThan(10);
    await expect(page.locator('.prose')).not.toBeEmpty({ timeout: 65_000 });
    const prepAudio = page.waitForResponse((response) =>
        response.url().endsWith('/interview-prep/audio'),
    );
    await page.getByRole('button', { name: 'Read guide aloud' }).click();
    const audioResponse = await prepAudio;
    expect(audioResponse.status()).toBe(200);
    expect(audioResponse.headers()['content-type']).toMatch(/^audio\//);
    expect((await audioResponse.body()).length).toBeGreaterThan(100);
    await expect(page.getByRole('status')).toHaveText('Speaking', {
        timeout: 45_000,
    });
    await page.getByRole('button', { name: 'Stop audio', exact: true }).click();
    await page.getByRole('button', { name: /^Start AI Interview/ }).click();
    await expect(page).toHaveURL(/\/interview-sessions\/\d+$/);
    await expect(
        page.getByRole('button', { name: 'Play audio', exact: true }),
    ).toBeVisible({ timeout: 90_000 });
    const transcription = page.waitForResponse((response) =>
        /\/interview-sessions\/\d+\/transcribe$/.test(response.url()),
    );
    const nextQuestion = page.waitForResponse((response) =>
        /\/interview-sessions\/\d+\/message$/.test(response.url()),
    );
    await record(page, 'Stop recording and send answer');
    const transcriptResponse = await transcription;
    expect(transcriptResponse.status()).toBe(200);
    const transcript = (await transcriptResponse.json()).text;
    expect(transcript.length).toBeGreaterThan(10);
    const messageResponse = await nextQuestion;
    expect(messageResponse.status()).toBe(200);
    expect((await messageResponse.json()).question_number).toBe(2);
    await expect(
        page.getByText(transcript, { exact: true }).first(),
    ).toBeVisible();
    await testInfo.attach('real-audio-chain', {
        body: JSON.stringify({
            prepSTT: 200,
            prepTTS: 200,
            interviewSTT: 200,
            questionNumber: 2,
            fixtureCapture: true,
        }),
        contentType: 'application/json',
    });
});
