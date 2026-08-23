<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Sparkles } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AlertError from '@/components/AlertError.vue';
import AdditionalInfoForm from '@/components/ResumeEditor/AdditionalInfoForm.vue';
import EducationForm from '@/components/ResumeEditor/EducationForm.vue';
import LeadershipForm from '@/components/ResumeEditor/LeadershipForm.vue';
import AwardsHonorsForm from '@/components/ResumeEditor/AwardsHonorsForm.vue';
import LanguagesForm from '@/components/ResumeEditor/LanguagesForm.vue';
import PublicationsForm from '@/components/ResumeEditor/PublicationsForm.vue';
import PersonalInfoForm from '@/components/ResumeEditor/PersonalInfoForm.vue';
import ProjectsForm from '@/components/ResumeEditor/ProjectsForm.vue';
import ResumeAiAnalysis from '@/components/ResumeEditor/ResumeAiAnalysis.vue';
import ResumeEditorSidebar from '@/components/ResumeEditor/ResumeEditorSidebar.vue';
import ResumeSummary from '@/components/ResumeEditor/ResumeSummary.vue';
import SkillsForm from '@/components/ResumeEditor/SkillsForm.vue';
import VolunteerForm from '@/components/ResumeEditor/VolunteerForm.vue';
import WorkExperienceForm from '@/components/ResumeEditor/WorkExperienceForm.vue';
import resumeEditor from '@/routes/resume-editor';
import resumes from '@/routes/resumes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Resumes',
                href: '/resumes',
            },
            {
                title: 'Resume Editor',
                href: '#',
            },
        ],
    },
});

const page = usePage();
const currentSection = ref<string>('personalInfo');

const props = computed(() => page.props as any);

const sectionComponents: Record<string, any> = {
    personalInfo: PersonalInfoForm,
    workExperience: WorkExperienceForm,
    education: EducationForm,
    skills: SkillsForm,
    projects: ProjectsForm,
    volunteer: VolunteerForm,
    leadership: LeadershipForm,
    publications: PublicationsForm,
    awardsHonors: AwardsHonorsForm,
    languages: LanguagesForm,
    additionalInfo: AdditionalInfoForm,
    summary: ResumeSummary,
    aiAnalysis: ResumeAiAnalysis,
};

const currentComponent = computed(() => {
    return sectionComponents[currentSection.value];
});
</script>

<template>
    <Head title="Resume Editor" />

    <div class="flex h-full flex-1 gap-0 overflow-hidden">
        <!-- Inner Sidebar -->
        <ResumeEditorSidebar
            :current-section="currentSection"
            @select-section="currentSection = $event"
        />

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="mx-auto max-w-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <Link
                        :href="resumes.index()"
                        class="flex items-center gap-1 text-sm text-foreground/60 hover:text-foreground"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        My Resumes
                    </Link>
                    <span class="text-sm font-medium text-foreground/70">
                        {{ props.resume?.title }}
                    </span>
                    <Link
                        v-if="props.resume?.id"
                        :href="resumeEditor.assistant.url(props.resume.id)"
                        class="flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        <Sparkles class="h-4 w-4" />
                        Build with AI
                    </Link>
                </div>

                <!-- Error Alert -->
                <AlertError
                    v-if="props.errors && Object.keys(props.errors).length > 0"
                    :errors="props.errors"
                    class="mb-4"
                />

                <component
                    :is="currentComponent"
                    :resume="props.resume"
                    :user="props.user"
                    :profile="props.profile"
                    :work-experiences="props.workExperiences"
                    :educations="props.educations"
                    :skills="props.skills"
                    :projects="props.projects"
                    :volunteer-experiences="props.volunteerExperiences"
                    :leadership-activities="props.leadershipActivities"
                    :publications="props.publications"
                    :award-honors="props.awardHonors"
                    :languages="props.languages"
                    :additional-info="props.additionalInfo"
                    :ai-messages="props.aiMessages"
                    @next-section="currentSection = $event"
                />
            </div>
        </div>
    </div>
</template>

