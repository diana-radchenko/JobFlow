<script setup lang="ts">
import {
    User,
    Briefcase,
    BookOpen,
    Award,
    FolderOpen,
    HeartHandshake,
    Users,
    Zap,
    Edit2,
    ChevronLeft,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { stringForHuman } from '@/helpers/strings';
import type { WorkExperience } from '@/types/laravel-models';

interface Props {
    resume: { id: number; title: string };
    user: any;
    profile: any;
    workExperiences: any[];
    educations: any[];
    skills: any[];
    projects: any[];
    volunteerExperiences: any[];
    leadershipActivities: any[];
    additionalInfo: any;
}

const props = defineProps<Props>();

defineEmits<{
    nextSection: [section: string];
}>();

const includedWorkExperiences = computed(() =>
    props.workExperiences.filter((exp) => exp.included),
);
const includedEducations = computed(() =>
    props.educations.filter((edu) => edu.included),
);
const includedSkills = computed(() => props.skills.filter((s) => s.included));
const includedProjects = computed(() =>
    props.projects.filter((p) => p.included),
);
const includedVolunteerExperiences = computed(() =>
    props.volunteerExperiences.filter((v) => v.included),
);
const includedLeadershipActivities = computed(() =>
    props.leadershipActivities.filter((l) => l.included),
);

const formatActivityLocationLine = (item: any): string =>
    [item.city, item.country]
        .filter((x) => x != null && String(x).trim() !== '')
        .join(', ');

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
    });
};

const formatBirthDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const displayFullName = () => {
    const p = props.profile;

    if (!p) {
        return props.user?.name ?? '';
    }

    const parts = [p.first_name, p.middle_name, p.last_name].filter(
        (x) => x != null && String(x).trim() !== '',
    );

    if (parts.length) {
        return parts.join(' ');
    }

    return props.user?.name ?? '';
};

const formatWorkLocationLine = (exp: WorkExperience): string => {
    const place = [exp.city, exp.country]
        .filter((x) => x != null && String(x).trim() !== '')
        .join(', ');

    if (exp.is_remote && place) {
        return `Remote · ${place}`;
    }

    if (exp.is_remote) {
        return 'Remote';
    }

    return place;
};
</script>

<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>{{ resume.title }}</CardTitle>
                <CardDescription>
                    Review this resume before finalizing — only the items you've
                    included are shown below
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-8">
                <!-- Personal Info Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b pb-2">
                        <User class="h-5 w-5 text-primary" />
                        <h3 class="text-lg font-semibold">
                            Personal Information
                        </h3>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('nextSection', 'personalInfo')"
                            class="ml-auto"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div class="space-y-2 text-sm">
                        <p v-if="displayFullName()">
                            <strong>Name:</strong> {{ displayFullName() }}
                        </p>
                        <p><strong>Email:</strong> {{ user.email }}</p>
                        <p v-if="profile?.date_of_birth">
                            <strong>Date of birth:</strong>
                            {{ formatBirthDate(profile.date_of_birth) }}
                        </p>
                        <p v-if="profile?.phone">
                            <strong>Phone:</strong> {{ profile.phone }}
                        </p>
                        <p
                            v-if="profile?.city || profile?.country"
                            class="flex flex-wrap gap-x-1"
                        >
                            <strong>Location:</strong>
                            <span>
                                {{
                                    [profile?.city, profile?.country]
                                        .filter(Boolean)
                                        .join(', ')
                                }}
                            </span>
                        </p>
                        <p v-if="profile?.linkedin_url">
                            <strong>LinkedIn:</strong>
                            <a
                                :href="profile.linkedin_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary hover:underline"
                            >
                                View Profile
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Work Experience Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b pb-2">
                        <Briefcase class="h-5 w-5 text-primary" />
                        <h3 class="text-lg font-semibold">Work Experience</h3>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('nextSection', 'workExperience')"
                            class="ml-auto"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div
                        v-if="includedWorkExperiences.length > 0"
                        class="space-y-4"
                    >
                        <div
                            v-for="exp in includedWorkExperiences"
                            :key="exp.id"
                            class="rounded-lg border border-border p-3"
                        >
                            <h4 class="font-semibold">{{ exp.job_title }}</h4>
                            <p class="text-sm text-foreground/70">
                                {{ exp.company_name }}
                            </p>
                            <p class="mt-1 text-xs text-foreground/60">
                                {{ formatDate(exp.start_date) }} -
                                <span v-if="exp.is_current">Present</span>
                                <span v-else>{{
                                    exp.end_date
                                        ? formatDate(exp.end_date)
                                        : '—'
                                }}</span>
                            </p>
                            <p
                                v-if="formatWorkLocationLine(exp)"
                                class="text-xs text-foreground/60"
                            >
                                📍 {{ formatWorkLocationLine(exp) }}
                            </p>
                            <p
                                v-if="exp.description"
                                class="mt-2 text-sm text-foreground/70"
                            >
                                {{ exp.description }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-foreground/60 italic">
                        No work experience added yet
                    </p>
                </div>

                <!-- Education Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b pb-2">
                        <BookOpen class="h-5 w-5 text-primary" />
                        <h3 class="text-lg font-semibold">Education</h3>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('nextSection', 'education')"
                            class="ml-auto"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div v-if="includedEducations.length > 0" class="space-y-4">
                        <div
                            v-for="edu in includedEducations"
                            :key="edu.id"
                            class="rounded-lg border border-border p-3"
                        >
                            <h4 class="font-semibold">
                                {{ stringForHuman(edu.degree) }}
                            </h4>
                            <p class="text-sm text-foreground/70">
                                {{ edu.institution }}
                            </p>
                            <p
                                v-if="edu.field_of_study"
                                class="text-sm text-foreground/70"
                            >
                                {{ edu.field_of_study }}
                            </p>
                            <p
                                v-if="edu.start_date"
                                class="mt-1 text-xs text-foreground/60"
                            >
                                {{ formatDate(edu.start_date) }}
                                <span v-if="edu.end_date"
                                    >- {{ formatDate(edu.end_date) }}</span
                                >
                            </p>
                            <p
                                v-if="edu.description"
                                class="mt-2 text-sm text-foreground/70"
                            >
                                {{ edu.description }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-foreground/60 italic">
                        No education added yet
                    </p>
                </div>

                <!-- Skills Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b pb-2">
                        <Award class="h-5 w-5 text-primary" />
                        <h3 class="text-lg font-semibold">
                            Skills & Competencies
                        </h3>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('nextSection', 'skills')"
                            class="ml-auto"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div
                        v-if="includedSkills.length > 0"
                        class="flex flex-wrap gap-2"
                    >
                        <span
                            v-for="skill in includedSkills"
                            :key="skill.id"
                            class="inline-block rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                        >
                            {{ skill.name }} •
                            {{ stringForHuman(skill.proficiency_level) }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-foreground/60 italic">
                        No skills added yet
                    </p>
                </div>

                <!-- Projects Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b pb-2">
                        <FolderOpen class="h-5 w-5 text-primary" />
                        <h3 class="text-lg font-semibold">
                            Projects, Achievements & Research
                        </h3>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('nextSection', 'projects')"
                            class="ml-auto"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div v-if="includedProjects.length > 0" class="space-y-4">
                        <div
                            v-for="proj in includedProjects"
                            :key="proj.id"
                            class="rounded-lg border border-border p-3"
                        >
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold">{{ proj.title }}</h4>
                                <span
                                    class="rounded bg-primary/10 px-2 py-1 text-xs text-primary"
                                >
                                    {{ stringForHuman(proj.type) }}
                                </span>
                            </div>
                            <p
                                v-if="proj.start_date"
                                class="mt-1 text-xs text-foreground/60"
                            >
                                {{ formatDate(proj.start_date) }}
                                <span v-if="proj.end_date"
                                    >- {{ formatDate(proj.end_date) }}</span
                                >
                            </p>
                            <p
                                v-if="proj.description"
                                class="mt-2 text-sm text-foreground/70"
                            >
                                {{ proj.description }}
                            </p>
                            <a
                                v-if="proj.url"
                                :href="proj.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-2 inline-block text-xs text-primary hover:underline"
                            >
                                View Project →
                            </a>
                        </div>
                    </div>
                    <p v-else class="text-sm text-foreground/60 italic">
                        No projects added yet
                    </p>
                </div>

                <!-- Volunteer & Community Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b pb-2">
                        <HeartHandshake class="h-5 w-5 text-primary" />
                        <h3 class="text-lg font-semibold">
                            Volunteer &amp; Community
                        </h3>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('nextSection', 'volunteer')"
                            class="ml-auto"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div
                        v-if="includedVolunteerExperiences.length > 0"
                        class="space-y-4"
                    >
                        <div
                            v-for="item in includedVolunteerExperiences"
                            :key="item.id"
                            class="rounded-lg border border-border p-3"
                        >
                            <h4 class="font-semibold">{{ item.role }}</h4>
                            <p class="text-sm text-foreground/70">
                                {{ item.organization }}
                            </p>
                            <p class="mt-1 text-xs text-foreground/60">
                                {{ formatDate(item.start_date) }} -
                                <span v-if="item.is_current">Present</span>
                                <span v-else>{{
                                    item.end_date
                                        ? formatDate(item.end_date)
                                        : '—'
                                }}</span>
                            </p>
                            <p
                                v-if="formatActivityLocationLine(item)"
                                class="text-xs text-foreground/60"
                            >
                                📍 {{ formatActivityLocationLine(item) }}
                            </p>
                            <p
                                v-if="item.description"
                                class="mt-2 text-sm text-foreground/70"
                            >
                                {{ item.description }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-foreground/60 italic">
                        No volunteer experience added yet
                    </p>
                </div>

                <!-- Leadership & Extracurricular Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b pb-2">
                        <Users class="h-5 w-5 text-primary" />
                        <h3 class="text-lg font-semibold">
                            Leadership &amp; Extracurricular
                        </h3>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('nextSection', 'leadership')"
                            class="ml-auto"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div
                        v-if="includedLeadershipActivities.length > 0"
                        class="space-y-4"
                    >
                        <div
                            v-for="item in includedLeadershipActivities"
                            :key="item.id"
                            class="rounded-lg border border-border p-3"
                        >
                            <h4 class="font-semibold">{{ item.role }}</h4>
                            <p class="text-sm text-foreground/70">
                                {{ item.organization }}
                            </p>
                            <p class="mt-1 text-xs text-foreground/60">
                                {{ formatDate(item.start_date) }} -
                                <span v-if="item.is_current">Present</span>
                                <span v-else>{{
                                    item.end_date
                                        ? formatDate(item.end_date)
                                        : '—'
                                }}</span>
                            </p>
                            <p
                                v-if="formatActivityLocationLine(item)"
                                class="text-xs text-foreground/60"
                            >
                                📍 {{ formatActivityLocationLine(item) }}
                            </p>
                            <p
                                v-if="item.description"
                                class="mt-2 text-sm text-foreground/70"
                            >
                                {{ item.description }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-foreground/60 italic">
                        No leadership activities added yet
                    </p>
                </div>

                <!-- Additional Info Section -->
                <div v-if="additionalInfo" class="space-y-4">
                    <div class="flex items-center gap-2 border-b pb-2">
                        <Zap class="h-5 w-5 text-primary" />
                        <h3 class="text-lg font-semibold">
                            Additional Information
                        </h3>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('nextSection', 'additionalInfo')"
                            class="ml-auto"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div v-if="additionalInfo.languages?.length">
                            <strong>Languages:</strong>
                            <p class="text-foreground/70">
                                {{ additionalInfo.languages.join(', ') }}
                            </p>
                        </div>
                        <div v-if="additionalInfo.certifications">
                            <strong>Certifications:</strong>
                            <p class="whitespace-pre-wrap text-foreground/70">
                                {{ additionalInfo.certifications }}
                            </p>
                        </div>
                        <div v-if="additionalInfo.interests?.length">
                            <strong>Interests:</strong>
                            <p class="text-foreground/70">
                                {{ additionalInfo.interests.join(', ') }}
                            </p>
                        </div>
                        <div v-if="additionalInfo.notes">
                            <strong>Notes:</strong>
                            <p class="whitespace-pre-wrap text-foreground/70">
                                {{ additionalInfo.notes }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('nextSection', 'additionalInfo')"
                    >
                        <ChevronLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <!-- <Button type="button" disabled class="ml-auto">
                        <Download class="h-4 w-4 mr-2" />
                        Download Resume (Coming Soon)
                    </Button> -->
                </div>
            </CardContent>
        </Card>
    </div>
</template>
