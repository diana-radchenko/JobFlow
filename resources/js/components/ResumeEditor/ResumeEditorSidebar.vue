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
    <div class="hidden w-64 border-r border-sidebar-border bg-sidebar md:flex md:flex-col md:gap-0">
        <div class="flex flex-1 flex-col gap-2 overflow-y-auto px-4 py-6">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-foreground/70">Build Your Resume</h3>
            </div>
            
            <div class="flex flex-col gap-2">
                <button
                    v-for="section in sections"
                    :key="section.id"
                    @click="emit('selectSection', section.id)"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                        currentSection === section.id
                            ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                            : 'text-sidebar-foreground hover:bg-sidebar-accent/10'
                    ]"
                >
                    <component :is="section.icon" class="h-4 w-4 flex-shrink-0" />
                    <div class="flex-1 text-left">
                        <div class="font-medium">{{ section.label }}</div>
                        <div class="text-xs opacity-60">{{ section.description }}</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
