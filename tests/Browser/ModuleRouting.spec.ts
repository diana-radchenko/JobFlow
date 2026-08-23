import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

const password = 'FlowTest!2026';
const runId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
const candidateEmail = `candidate-${runId}@example.test`;
const employerEmail = `employer-${runId}@example.test`;

async function register(
    page: Page,
    type: 'candidate' | 'employer',
    email: string,
) {
    await page.goto(`/register?type=${type}`);
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByLabel('Confirm password').fill(password);
    await page.getByTestId('register-user-button').click();
}

async function login(
    page: Page,
    type: 'candidate' | 'employer',
    email: string,
) {
    await page.goto(`/login?type=${type}`);
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
}

test.describe.serial('JobFlow and HRFlow module routing', () => {
    test('guest defaults to candidate registration and cards update the URL', async ({
        page,
    }) => {
        await page.goto('/');

        await expect(page).toHaveURL(/\/register\?type=candidate$/);
        await expect(
            page.getByTestId('register-profile-type-candidate'),
        ).toHaveAttribute('aria-checked', 'true');

        await page.getByTestId('register-profile-type-employer').click();
        await expect(page).toHaveURL(/\/register\?type=employer$/);
        await expect(
            page.getByTestId('register-profile-type-employer'),
        ).toHaveAttribute('aria-checked', 'true');
    });

    test('candidate registration and returning login stay in JobFlow', async ({
        browser,
    }) => {
        const context = await browser.newContext();
        const page = await context.newPage();

        await register(page, 'candidate', candidateEmail);
        await expect(page).toHaveURL(/\/resumes(?:\?.*)?$/);

        await page.goto('/');
        await expect(page).toHaveURL(/\/resumes(?:\?.*)?$/);

        await context.clearCookies();
        await login(page, 'candidate', candidateEmail);
        await expect(page).toHaveURL(/\/resumes(?:\?.*)?$/);

        await context.close();
    });

    test('employer registration and returning login stay in HRFlow', async ({
        browser,
    }) => {
        const context = await browser.newContext();
        const page = await context.newPage();

        await register(page, 'employer', employerEmail);
        await expect(page).toHaveURL(/\/employer\/jobs(?:\?.*)?$/);

        await page.goto('/');
        await expect(page).toHaveURL(/\/employer\/jobs(?:\?.*)?$/);

        await context.clearCookies();
        await login(page, 'employer', employerEmail);
        await expect(page).toHaveURL(/\/employer\/jobs(?:\?.*)?$/);

        await context.close();
    });

    test('candidate can enter HRFlow safely without changing role', async ({
        browser,
    }) => {
        const context = await browser.newContext();
        const page = await context.newPage();

        await login(page, 'candidate', candidateEmail);
        await page.goto('/hrflow');

        await expect(page).toHaveURL(/\/hrflow$/);
        await expect(
            page.getByText(
                'You are currently signed in with a candidate account.',
            ),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'Return to JobFlow' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', {
                name: 'Sign Out and Continue to HRFlow',
            }),
        ).toBeVisible();

        await page.goto('/register?type=employer');
        await expect(page).toHaveURL(/\/hrflow\?intent=register$/);
        await page.getByRole('link', { name: 'Return to JobFlow' }).click();
        await expect(page).toHaveURL(/\/resumes(?:\?.*)?$/);

        await context.close();
    });

    test('employer can enter JobFlow safely without 403', async ({
        browser,
    }) => {
        const context = await browser.newContext();
        const page = await context.newPage();

        await login(page, 'employer', employerEmail);
        await page.goto('/jobflow');

        await expect(page).toHaveURL(/\/jobflow$/);
        await expect(
            page.getByText(
                'You are currently signed in with an employer account.',
            ),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'Return to HRFlow' }),
        ).toBeVisible();

        await context.close();
    });
});
