export type AdditionalInformation = {
  // columns
  id: number
  user_id: number
  languages: string | null
  certifications: string | null
  interests: string | null
  notes: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  user: User
  // counts
  // exists
  user_exists: boolean
}

export type Education = {
  // columns
  id: number
  user_id: number
  degree: string
  institution: string
  field_of_study: string | null
  start_date: string | null
  end_date: string | null
  description: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  user: User
  // counts
  // exists
  user_exists: boolean
}

export type Project = {
  // columns
  id: number
  user_id: number
  title: string
  type: string
  description: string | null
  url: string | null
  start_date: string | null
  end_date: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  user: User
  // counts
  // exists
  user_exists: boolean
}

export type Skill = {
  // columns
  id: number
  user_id: number
  name: string
  proficiency_level: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  user: User
  // counts
  // exists
  user_exists: boolean
}

export type User = {
  // columns
  id: number
  name: string
  email: string
  email_verified_at: string | null
  password?: string
  remember_token?: string | null
  created_at: string | null
  updated_at: string | null
  two_factor_secret?: string | null
  two_factor_recovery_codes?: string | null
  two_factor_confirmed_at: string | null
  // relations
  profile: UserProfile
  educations: Education[]
  work_experiences: WorkExperience[]
  skills: Skill[]
  projects: Project[]
  additional_information: AdditionalInformation
  notifications: DatabaseNotification[]
  // counts
  educations_count: number
  work_experiences_count: number
  skills_count: number
  projects_count: number
  notifications_count: number
  // exists
  profile_exists: boolean
  educations_exists: boolean
  work_experiences_exists: boolean
  skills_exists: boolean
  projects_exists: boolean
  additional_information_exists: boolean
  notifications_exists: boolean
}

export type UserProfile = {
  // columns
  id: number
  user_id: number
  phone: string | null
  linkedin_url: string | null
  location: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  user: User
  // counts
  // exists
  user_exists: boolean
}

export type WorkExperience = {
  // columns
  id: number
  user_id: number
  company_name: string
  job_title: string
  location: string | null
  start_date: string
  end_date: string | null
  is_current: boolean
  description: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  user: User
  // counts
  // exists
  user_exists: boolean
}

export type WorkJob = {
  // columns
  id: number
  title: string
  salary_start: number | null
  salary_end: number | null
  company: string
  description: string
  contacts: string
  location: string
  technologies: string[]
  created_at: string | null
  updated_at: string | null
}

