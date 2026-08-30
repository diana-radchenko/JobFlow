export type InterviewHistorySession = {
    id: number;
    type: string;
    complexity: string;
    mode: string;
    status: string;
    created_at: string;
    resume: { id: number; title: string } | null;
    work_job: { id: number; title: string; company: string } | null;
};

export type UpcomingInterview = {
    id: number;
    resume_id: number | null;
    work_job_id: number;
    application_id: number | null;
    scheduled_at: string;
    timezone: string;
    interview_format: string | null;
    work_job: { id: number; title: string; company: string } | null;
};
