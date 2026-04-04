<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { 
    User, 
    Briefcase, 
    BookOpen, 
    Award, 
    FolderOpen, 
    Zap, 
    FileText 
} from 'lucide-vue-next';

interface Props {
    currentSection: string;
}

defineProps<Props>();

const emit = defineEmits<{
    selectSection: [section: string];
}>();

const sections = computed(() => [
    {
        id: 'personalInfo',
        label: 'Personal Info',
        icon: User,
        description: 'Your basic information',
    },
    {
        id: 'workExperience',
        label: 'Work Experience',
        icon: Briefcase,
        description: 'Your professional history',
    },
    {
        id: 'education',
        label: 'Education',
        icon: BookOpen,
        description: 'Your qualifications',
    },
    {
        id: 'skills',
        label: 'Skills & Competencies',
        icon: Award,
        description: 'Your expertise',
    },
    {
        id: 'projects',
        label: 'Projects & Achievements',
        icon: FolderOpen,
        description: 'Your work samples',
    },
    {
        id: 'additionalInfo',
        label: 'Additional Info',
        icon: Zap,
        description: 'Languages & certifications',
    },
    {
        id: 'summary',
        label: 'Summary',
        icon: FileText,
        description: 'Review your resume',
    },
]);
</script>

<template>
    <div class="hidden mt-6 ml-6 mb-6 w-72 border rounded-xl bg-blueish md:flex md:flex-col md:gap-0 md:p-6">
        <div class="flex flex-1 flex-col gap-4 overflow-y-auto">
            <div class="mb-2">
                <h3 class="text-sm font-semibold text-foreground/70">Summary sections</h3>
            </div>
            
            <div class="flex flex-col gap-3">
                <button
                    v-for="section in sections"
                    :key="section.id"
                    @click="emit('selectSection', section.id)"
                    :class="[
                        'flex items-center gap-3 rounded-full px-4 py-3 text-sm font-medium transition-all duration-200',
                        currentSection === section.id
                            ? 'bg-sidebar-accent text-sidebar-accent-foreground shadow-md'
                            : 'bg-white text-sidebar-foreground hover:shadow-sm dark:bg-slate-800 dark:text-slate-100'
                    ]"
                >
                    <component :is="section.icon" class="h-4 w-4 flex-shrink-0" />
                    <span>{{ section.label }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
