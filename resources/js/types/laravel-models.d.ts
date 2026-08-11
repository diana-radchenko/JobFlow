import type {
    EducationDegreeEnum,
    ProjectTypeEnum,
    SkillsLevelEnum,
    ApplicationStatusEnum,
} from '@/enums/laravel-models-enums';

export type AdditionalInformation = {
    // columns
    id: number;
    user_id: number;
    resume_id: number | null;
    languages: string[] | null;
    certifications: string | null;
    interests: string[] | null;
    notes: string | null;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    resume: Resume;
    // counts
    // exists
    user_exists: boolean;
    resume_exists: boolean;
};

export type Education = {
    // columns
    id: number;
    user_id: number;
    degree: EducationDegree;
    institution: string;
    field_of_study: string;
    start_date: string;
    end_date: string | null;
    description: string | null;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    // counts
    // exists
    user_exists: boolean;
    // resume inclusion (present when returned from ResumeEditorController)
    included?: boolean;
    order?: number | null;
};

export type InterviewSession = {
    // columns
    id: number;
    user_id: number;
    conversation_id: string | null;
    type: string;
    complexity: string;
    mode: string;
    status: string;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    // counts
    // exists
    user_exists: boolean;
};

export type Project = {
    // columns
    id: number;
    user_id: number;
    title: string;
    type: ProjectType;
    description: string | null;
    url: string | null;
    start_date: string | null;
    end_date: string | null;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    // counts
    // exists
    user_exists: boolean;
    // resume inclusion (present when returned from ResumeEditorController)
    included?: boolean;
    order?: number | null;
};

export type VolunteerExperience = {
    // columns
    id: number;
    user_id: number;
    organization: string;
    role: string;
    description: string | null;
    url: string | null;
    city: string | null;
    country: string | null;
    start_date: string;
    end_date: string | null;
    is_current: boolean;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    // counts
    // exists
    user_exists: boolean;
    // resume inclusion (present when returned from ResumeEditorController)
    included?: boolean;
    order?: number | null;
};

export type LeadershipActivity = {
    // columns
    id: number;
    user_id: number;
    organization: string;
    role: string;
    description: string | null;
    url: string | null;
    city: string | null;
    country: string | null;
    start_date: string;
    end_date: string | null;
    is_current: boolean;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    // counts
    // exists
    user_exists: boolean;
    // resume inclusion (present when returned from ResumeEditorController)
    included?: boolean;
    order?: number | null;
};

export type Resume = {
    // columns
    id: number;
    user_id: number;
    title: string;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    skills: Skill[];
    projects: Project[];
    educations: Education[];
    work_experiences: WorkExperience[];
    volunteer_experiences: VolunteerExperience[];
    leadership_activities: LeadershipActivity[];
    additional_information: AdditionalInformation;
    // counts
    skills_count: number;
    projects_count: number;
    educations_count: number;
    work_experiences_count: number;
    volunteer_experiences_count: number;
    leadership_activities_count: number;
    // exists
    user_exists: boolean;
    additional_information_exists: boolean;
};

export type Skill = {
    // columns
    id: number;
    user_id: number;
    name: string;
    proficiency_level: SkillsLevel;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    // counts
    // exists
    user_exists: boolean;
    // resume inclusion (present when returned from ResumeEditorController)
    included?: boolean;
    order?: number | null;
};

export type User = {
    // columns
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    password?: string;
    remember_token?: string | null;
    created_at: string | null;
    updated_at: string | null;
    two_factor_secret?: string | null;
    two_factor_recovery_codes?: string | null;
    two_factor_confirmed_at: string | null;
    // relations
    profile: UserProfile;
    educations: Education[];
    work_experiences: WorkExperience[];
    skills: Skill[];
    projects: Project[];
    additional_information: AdditionalInformation;
    applications: UserWorkJobApplication[];
    applied_jobs: WorkJob[];
    notifications: DatabaseNotification[];
    resumes: Resume[];
    // counts
    educations_count: number;
    work_experiences_count: number;
    skills_count: number;
    projects_count: number;
    applications_count: number;
    applied_jobs_count: number;
    notifications_count: number;
    resumes_count: number;
    // exists
    profile_exists: boolean;
    educations_exists: boolean;
    work_experiences_exists: boolean;
    skills_exists: boolean;
    projects_exists: boolean;
    additional_information_exists: boolean;
    applications_exists: boolean;
    applied_jobs_exists: boolean;
    notifications_exists: boolean;
    resumes_exists: boolean;
};

export type UserProfile = {
    // columns
    id: number;
    user_id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    date_of_birth: string | null;
    phone: string | null;
    linkedin_url: string | null;
    city: string | null;
    country: string | null;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    // counts
    // exists
    user_exists: boolean;
};

export type UserWorkJobApplication = {
    // columns
    id: number;
    user_id: number;
    work_job_id: number;
    resume_id: number | null;
    status: ApplicationStatus;
    viewed_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    work_job: WorkJob;
    resume: Resume;
    // counts
    // exists
    user_exists: boolean;
    work_job_exists: boolean;
    resume_exists: boolean;
};

export type WorkExperience = {
    // columns
    id: number;
    user_id: number;
    company_name: string;
    job_title: string;
    city: string | null;
    country: string | null;
    is_remote: boolean;
    start_date: string;
    end_date: string | null;
    is_current: boolean;
    description: string | null;
    created_at: string | null;
    updated_at: string | null;
    // relations
    user: User;
    // counts
    // exists
    user_exists: boolean;
    // resume inclusion (present when returned from ResumeEditorController)
    included?: boolean;
    order?: number | null;
};

export type WorkJob = {
    // columns
    id: number;
    user_id: number | null;
    title: string;
    salary_start: number | null;
    salary_end: number | null;
    company: string;
    description: string;
    contacts: string;
    location: string;
    technologies: Array<unknown>;
    created_at: string | null;
    updated_at: string | null;
    // relations
    employer: User | null;
    applications: UserWorkJobApplication[];
    applicants: User[];
    // counts
    applications_count: number;
    applicants_count: number;
    // exists
    applications_exists: boolean;
    applicants_exists: boolean;
};

export type EducationDegree =
    (typeof EducationDegreeEnum)[keyof typeof EducationDegreeEnum];
export type ProjectType =
    (typeof ProjectTypeEnum)[keyof typeof ProjectTypeEnum];
export type SkillsLevel =
    (typeof SkillsLevelEnum)[keyof typeof SkillsLevelEnum];
export type ApplicationStatus =
    (typeof ApplicationStatusEnum)[keyof typeof ApplicationStatusEnum];
