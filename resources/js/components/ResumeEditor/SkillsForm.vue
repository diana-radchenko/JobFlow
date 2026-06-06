<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Trash2,
    Edit2,
    Save,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { SkillsLevelEnum } from '@/enums/laravel-models-enums';
import { stringForHuman } from '@/helpers/strings';
import type { SkillsLevel } from '@/types/laravel-models';

defineProps<{
    skills: any[];
}>();

interface Emits {
    nextSection: [section: string];
}

defineEmits<Emits>();

const proficiencyLevels: SkillsLevel[] = Object.values(SkillsLevelEnum);

const form = useForm({
    name: '',
    proficiency_level: SkillsLevelEnum.Beginner,
});

const showForm = ref(false);
const editingId = ref<number | null>(null);

const resetForm = () => {
    form.reset();
    editingId.value = null;
    showForm.value = false;
};

const submit = () => {
    if (editingId.value) {
        form.put(`/resume-editor/skill/${editingId.value}`, {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post('/resume-editor/skill', {
            onSuccess: () => resetForm(),
        });
    }
};

const editSkill = (skill: any) => {
    editingId.value = skill.id;
    form.name = skill.name;
    form.proficiency_level = skill.proficiency_level;
    showForm.value = true;
};

const deleteSkill = (id: number) => {
    if (confirm('Are you sure you want to delete this skill?')) {
        useForm().delete(`/resume-editor/skill/${id}`);
    }
};

const getProficiencyColor = (level: string) => {
    const colors: Record<string, string> = {
        Beginner:
            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        Intermediate:
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        Advanced:
            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        Expert: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };

    return colors[level] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>Skills & Competencies</CardTitle>
                <CardDescription>
                    Highlight your professional skills and expertise levels
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- Skills Grid -->
                <div v-if="skills.length > 0" class="grid gap-3">
                    <div
                        v-for="skill in skills"
                        :key="skill.id"
                        class="flex items-center justify-between gap-3 rounded-lg border border-border p-3"
                    >
                        <div>
                            <h4 class="font-medium text-foreground">
                                {{ skill.name }}
                            </h4>
                            <span
                                :class="[
                                    'mt-1 inline-block rounded px-2 py-1 text-xs',
                                    getProficiencyColor(
                                        skill.proficiency_level,
                                    ),
                                ]"
                            >
                                {{ stringForHuman(skill.proficiency_level) }}
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editSkill(skill)"
                            >
                                <Edit2 class="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="deleteSkill(skill.id)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div
                    v-if="showForm"
                    class="rounded-lg border border-border bg-muted/30 p-4"
                >
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label
                                for="skill-name"
                                class="mb-1 block text-sm font-medium"
                            >
                                Skill Name *
                            </label>
                            <Input
                                id="skill-name"
                                v-model="form.name"
                                placeholder="e.g., Python, Project Management, Leadership"
                                required
                            />
                        </div>

                        <div>
                            <label
                                for="proficiency"
                                class="mb-1 block text-sm font-medium"
                            >
                                Proficiency Level *
                            </label>
                            <Select v-model="form.proficiency_level">
                                <SelectTrigger id="proficiency">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="level in proficiencyLevels"
                                        :key="level"
                                        :value="level"
                                    >
                                        {{ stringForHuman(level) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex gap-3">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ editingId ? 'Update' : 'Add' }} Skill
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="resetForm"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Add button -->
                <div v-if="!showForm">
                    <Button
                        type="button"
                        variant="outline"
                        @click="showForm = true"
                        class="w-full"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Add Skill
                    </Button>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('nextSection', 'education')"
                    >
                        <ChevronLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <Button
                        type="button"
                        @click="$emit('nextSection', 'projects')"
                    >
                        Next
                        <ChevronRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
