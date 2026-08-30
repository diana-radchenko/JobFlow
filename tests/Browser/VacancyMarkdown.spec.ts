import { expect, test } from '@playwright/test';

const password = 'FlowTest!2026';
const runId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
const employerEmail = `markdown-employer-${runId}@example.test`;
const description = [
    '## Overview',
    '',
    'Build **safe** products.',
    '',
    '- Design the API',
    '- Review changes',
    '',
    '<script>window.__vacancyMarkdownXss = true</script>',
].join('\n');
const requirements = [
    '## Schedule',
    '',
    '- Monday to Friday',
    '- **Flexible** start time',
].join('\n');

test('vacancy markdown previews, persists, publishes, and resaves safely', async ({
    page,
}) => {
    await page.goto('/register?type=employer');
    await page.getByLabel('Email address').fill(employerEmail);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByLabel('Confirm password').fill(password);
    await page.getByTestId('register-user-button').click();

    await page.goto('/employer/jobs/create');
    await page.getByLabel('Job title').fill('Markdown Browser Vacancy');
    await page.getByLabel('Company').fill('Acme');
    await page.getByLabel('Location').fill('Remote');
    await page.getByLabel('Contact').fill('careers@acme.test');
    await page.getByLabel('Description').fill(description);
    await page.getByLabel('Requirements').fill(requirements);

    const descriptionEditor = page.getByLabel('Description').locator('..');
    await descriptionEditor.getByRole('button', { name: 'Preview' }).click();
    await expect(
        descriptionEditor.getByRole('heading', { name: 'Overview' }),
    ).toBeVisible();
    await expect(descriptionEditor.getByRole('listitem')).toHaveCount(2);
    await expect(descriptionEditor.locator('strong')).toHaveText('safe');
    await expect(descriptionEditor.locator('script')).toHaveCount(0);
    expect(
        await page.evaluate(() =>
            Boolean(
                (
                    window as typeof window & {
                        __vacancyMarkdownXss?: boolean;
                    }
                ).__vacancyMarkdownXss,
            ),
        ),
    ).toBe(false);

    await page.getByRole('button', { name: 'Post Job' }).click();
    await expect(page).toHaveURL(/\/employer\/jobs\/\d+$/);

    const publishedDescription = page
        .getByRole('heading', { name: 'Description', exact: true })
        .locator('..');
    await expect(
        publishedDescription.getByRole('heading', { name: 'Overview' }),
    ).toBeVisible();
    await expect(publishedDescription.getByRole('listitem')).toHaveCount(2);
    await expect(publishedDescription.locator('strong')).toHaveText('safe');
    await expect(publishedDescription.locator('script')).toHaveCount(0);

    const publishedRequirements = page
        .getByRole('heading', { name: 'Requirements', exact: true })
        .locator('..');
    await expect(
        publishedRequirements.getByRole('heading', { name: 'Schedule' }),
    ).toBeVisible();
    await expect(publishedRequirements.getByRole('listitem')).toHaveCount(2);
    await expect(publishedRequirements.locator('strong')).toHaveText(
        'Flexible',
    );

    await page.getByRole('link', { name: 'Edit' }).click();
    await expect(page.getByLabel('Description')).toHaveValue(description);
    await expect(page.getByLabel('Requirements')).toHaveValue(requirements);
    await page.getByRole('button', { name: 'Save Changes' }).click();

    await expect(page).toHaveURL(/\/employer\/jobs\/\d+$/);
    await expect(
        page
            .getByRole('heading', { name: 'Description', exact: true })
            .locator('..')
            .getByRole('heading', { name: 'Overview' }),
    ).toBeVisible();
});
