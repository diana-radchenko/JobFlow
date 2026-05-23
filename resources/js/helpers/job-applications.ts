import type { ApplicationStatus } from '@/types/laravel-models';

export const getApplicationStatusColor = (
    status: ApplicationStatus,
): string => {
    const colors: Record<ApplicationStatus, string> = {
        applied: 'status-grey',
        interview_scheduled: 'status-green',
        rejected: 'status-red',
        offer: 'status-green',
        hired: 'status-green',
    };
    return colors[status] || 'status-grey';
};
