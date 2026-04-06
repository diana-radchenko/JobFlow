import type { ApplicationStatus } from "@/types/laravel-models";
    
/**
* Converts an ApplicationStatus like "interview_scheduled" to "Interview Scheduled".
*/
export const getApplicationStatusLabel = (status: ApplicationStatus): string => {
   return status
       .split('_')
       .map((word: string) => word.charAt(0).toUpperCase() + word.slice(1))
       .join(' ');
};

export const getApplicationStatusColor = (status: ApplicationStatus): string => {
    const colors: Record<ApplicationStatus, string> = {
        applied: 'status-grey',
        interview_scheduled: 'status-green',
        rejected: 'status-red',
        offer: 'status-green',
        hired: 'status-green',
    };
    return colors[status] || 'status-grey';
};