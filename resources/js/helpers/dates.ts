/** HTML date inputs require `YYYY-MM-DD`; API dates are often ISO strings. */
export const formatDateForInput = (value: string | null | undefined): string =>
    value ? String(value).slice(0, 10) : '';
