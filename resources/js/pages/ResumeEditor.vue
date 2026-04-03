<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AlertError from '@/components/AlertError.vue';
import ResumeEditorSidebar from '@/components/ResumeEditor/ResumeEditorSidebar.vue';
import PersonalInfoForm from '@/components/ResumeEditor/PersonalInfoForm.vue';
import WorkExperienceForm from '@/components/ResumeEditor/WorkExperienceForm.vue';
import EducationForm from '@/components/ResumeEditor/EducationForm.vue';
import SkillsForm from '@/components/ResumeEditor/SkillsForm.vue';
import ProjectsForm from '@/components/ResumeEditor/ProjectsForm.vue';
import AdditionalInfoForm from '@/components/ResumeEditor/AdditionalInfoForm.vue';
import ResumeSummary from '@/components/ResumeEditor/ResumeSummary.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Resume Editor',
                href: '/resume-editor',
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
    additionalInfo: AdditionalInfoForm,
    summary: ResumeSummary,
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
                <!-- Error Alert -->
                <AlertError 
                    v-if="props.errors && Object.keys(props.errors).length > 0"
                    :errors="props.errors"
                    class="mb-4"
                />
                
                <component 
                    :is="currentComponent"
                    :user="props.user"
                    :profile="props.profile"
                    :work-experiences="props.workExperiences"
                    :educations="props.educations"
                    :skills="props.skills"
                    :projects="props.projects"
                    :additional-info="props.additionalInfo"
                    @next-section="currentSection = $event"
                />
            </div>
        </div>
    </div>
</template>
