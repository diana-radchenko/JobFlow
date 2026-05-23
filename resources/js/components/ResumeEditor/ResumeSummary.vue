<script setup lang="ts">
import {
    User,
    Briefcase,
    BookOpen,
    Award,
    FolderOpen,
    Zap,
    Edit2,
    ChevronLeft,
} from 'lucide-vue-next';
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
    user: any;
    profile: any;
    workExperiences: any[];
    educations: any[];
    skills: any[];
    projects: any[];
    additionalInfo: any;
}

const props = defineProps<Props>();

defineEmits<{
    nextSection: [section: string];
}>();

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
                <CardTitle>Resume Summary</CardTitle>
                <CardDescription>
                    Review your complete resume before finalizing
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
                    <div v-if="workExperiences.length > 0" class="space-y-4">
                        <div
                            v-for="exp in workExperiences"
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
                    <div v-if="educations.length > 0" class="space-y-4">
                        <div
                            v-for="edu in educations"
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
                    <div v-if="skills.length > 0" class="flex flex-wrap gap-2">
                        <span
                            v-for="skill in skills"
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
                            Projects & Achievements
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
                    <div v-if="projects.length > 0" class="space-y-4">
                        <div
                            v-for="proj in projects"
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
                        <div v-if="additionalInfo.languages">
                            <strong>Languages:</strong>
                            <p class="whitespace-pre-wrap text-foreground/70">
                                {{ additionalInfo.languages }}
                            </p>
                        </div>
                        <div v-if="additionalInfo.certifications">
                            <strong>Certifications:</strong>
                            <p class="whitespace-pre-wrap text-foreground/70">
                                {{ additionalInfo.certifications }}
                            </p>
                        </div>
                        <div v-if="additionalInfo.interests">
                            <strong>Interests:</strong>
                            <p class="whitespace-pre-wrap text-foreground/70">
                                {{ additionalInfo.interests }}
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
