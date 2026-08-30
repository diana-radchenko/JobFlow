<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Circle,
    Copy,
    FileText,
    Eye,
    Pencil,
    Plus,
    Sparkles,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import resumeEditor from '@/routes/resume-editor';
import resumesUrl from '@/routes/resumes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Resumes',
                href: '/resumes',
            },
        ],
    },
});

interface ResumeSummary {
    id: number;
    title: string;
    updated_at: string;
    skills_count: number;
    projects_count: number;
    educations_count: number;
    work_experiences_count: number;
    completeness: number;
    completeness_items: { label: string; complete: boolean; weight: number }[];
    is_primary: boolean;
}

const props = defineProps<{
    resumes: ResumeSummary[];
}>();

const normalizeResumeId = (id: number | string) => String(id);
const selectedResumeId = ref<string | null>(
    props.resumes[0] ? normalizeResumeId(props.resumes[0].id) : null,
);
const isSelectedResume = (resume: ResumeSummary) =>
    selectedResumeId.value === normalizeResumeId(resume.id);
const selectResume = (resume: ResumeSummary) => {
    selectedResumeId.value = normalizeResumeId(resume.id);
};
const selectedResume = computed(
    () => props.resumes.find((resume) => isSelectedResume(resume)) ?? null,
);

const setPrimaryResume = (resume: ResumeSummary) => {
    router.post(
        `/resumes/${resume.id}/primary`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedResumeId.value = normalizeResumeId(resume.id);
            },
        },
    );
};

const showCreateForm = ref(false);
const createForm = useForm({ title: '' });

const submitCreate = () => {
    createForm.post(resumesUrl.store.url(), {
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
        },
    });
};

const editingId = ref<number | null>(null);
const renameForm = useForm({ title: '' });

const startRename = (resume: ResumeSummary) => {
    editingId.value = resume.id;
    renameForm.title = resume.title;
};

const submitRename = (resume: ResumeSummary) => {
    renameForm.put(resumesUrl.update.url(resume.id), {
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const duplicateResume = (resume: ResumeSummary) => {
    useForm({}).post(resumesUrl.duplicate.url(resume.id));
};

const deleteResume = (resume: ResumeSummary) => {
    if (confirm(`Delete "${resume.title}"? This cannot be undone.`)) {
        useForm({}).delete(resumesUrl.destroy.url(resume.id));
    }
};

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
</script>

<template>
    <Head title="My Resumes" />

    <div class="jobflow-page dark:bg-slate-950">
        <div class="jobflow-page-frame">
            <div>
                <h1 class="jobflow-page-title">Resume Center</h1>
                <p class="text-sm text-foreground/60">
                    Keep separate resumes to highlight different skills and
                    experience for different job applications.
                </p>
            </div>
            <Button
                v-if="!showCreateForm"
                type="button"
                @click="showCreateForm = true"
            >
                <Plus class="mr-2 h-4 w-4" />
                New Resume
            </Button>

            <Card v-if="showCreateForm">
                <CardContent class="pt-6">
                    <form
                        @submit.prevent="submitCreate"
                        class="flex items-end gap-3"
                    >
                        <div class="flex-1">
                            <label
                                for="new-resume-title"
                                class="mb-1 block text-sm font-medium"
                            >
                                Resume title
                            </label>
                            <Input
                                id="new-resume-title"
                                v-model="createForm.title"
                                placeholder="e.g., Frontend Developer - Acme Corp"
                                required
                            />
                        </div>
                        <Button type="submit" :disabled="createForm.processing">
                            Create
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="
                                showCreateForm = false;
                                createForm.reset();
                            "
                        >
                            Cancel
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <div
                v-if="resumes.length === 0 && !showCreateForm"
                class="py-12 text-center"
            >
                <FileText class="mx-auto mb-3 h-10 w-10 text-foreground/30" />
                <p class="text-foreground/60">
                    Create your first resume to apply for jobs and receive
                    tailored recommendations.
                </p>
                <Button class="mt-4" @click="showCreateForm = true"
                    ><Plus class="mr-2 h-4 w-4" />Create Resume</Button
                >
            </div>

            <div
                class="grid gap-5 lg:grid-cols-[minmax(300px,0.75fr)_minmax(0,1.65fr)]"
            >
                <div
                    class="order-2 grid content-start gap-4 lg:grid-cols-1 xl:grid-cols-2"
                >
                    <Card
                        v-for="resume in resumes"
                        :key="resume.id"
                        class="cursor-pointer border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#0A2E48]/40 hover:shadow-md"
                        :class="{
                            'border-[#0A2E48] ring-2 ring-[#0A2E48]/20':
                                isSelectedResume(resume),
                        }"
                        @click="selectResume(resume)"
                    >
                        <CardHeader>
                            <form
                                v-if="editingId === resume.id"
                                @submit.prevent="submitRename(resume)"
                                @click.stop
                                class="flex gap-2"
                            >
                                <Input
                                    v-model="renameForm.title"
                                    autofocus
                                    required
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="renameForm.processing"
                                >
                                    Save
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click.stop="editingId = null"
                                >
                                    Cancel
                                </Button>
                            </form>
                            <template v-else>
                                <div
                                    class="flex flex-wrap items-start justify-between gap-2"
                                >
                                    <div>
                                        <CardTitle
                                            class="text-[16px] leading-snug font-semibold"
                                            >{{ resume.title }}</CardTitle
                                        >
                                        <CardDescription class="mt-1 text-sm">
                                            Updated
                                            {{ formatDate(resume.updated_at) }}
                                        </CardDescription>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            v-if="isSelectedResume(resume)"
                                            class="inline-flex items-center rounded-full border border-[#0A2E48]/35 bg-white px-2.5 py-1 text-[11px] font-semibold text-[#0A2E48]"
                                        >
                                            Selected
                                        </span>
                                        <span
                                            v-if="resume.is_primary"
                                            class="inline-flex items-center rounded-full bg-[#051C2E] px-3 py-1 text-[11px] font-bold tracking-wide text-white"
                                        >
                                            PRIMARY RESUME
                                        </span>
                                    </div>
                                </div>

                                <p
                                    v-if="resume.is_primary"
                                    class="mt-3 rounded-lg bg-[#F3F6F9] px-3 py-2 text-[12.5px] leading-relaxed text-[#475467]"
                                >
                                    Used by JobFlow by default for job matching
                                    and recommendations.
                                </p>
                            </template>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="rounded-xl bg-muted/60 p-4">
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <strong>Resume Completeness</strong
                                    ><span
                                        class="text-lg font-black text-primary"
                                        >{{ resume.completeness }}%
                                        Complete</span
                                    >
                                </div>
                                <div class="grid gap-2 text-sm sm:grid-cols-2">
                                    <span
                                        v-for="item in resume.completeness_items.slice(
                                            0,
                                            6,
                                        )"
                                        :key="item.label"
                                        class="flex items-center gap-2"
                                        :class="
                                            item.complete
                                                ? 'text-emerald-700 dark:text-emerald-300'
                                                : 'text-foreground/55'
                                        "
                                    >
                                        <CheckCircle2
                                            v-if="item.complete"
                                            class="h-4 w-4"
                                        /><Circle v-else class="h-4 w-4" />{{
                                            item.label
                                        }}
                                    </span>
                                </div>
                            </div>
                            <div
                                class="flex flex-wrap gap-4 text-sm text-foreground/60"
                            >
                                <span
                                    >{{
                                        resume.work_experiences_count
                                    }}
                                    experience</span
                                >
                                <span
                                    >{{
                                        resume.educations_count
                                    }}
                                    education</span
                                >
                                <span>{{ resume.skills_count }} skills</span>
                                <span
                                    >{{ resume.projects_count }} projects</span
                                >
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-if="!resume.is_primary"
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="border-[#0A2E48] text-[#0A2E48] hover:bg-[#F3F6F9]"
                                    @click.stop="setPrimaryResume(resume)"
                                >
                                    Set as Primary
                                </Button>

                                <Button as-child size="sm" @click.stop>
                                    <a :href="resumeEditor.show.url(resume.id)">
                                        Edit
                                    </a>
                                </Button>
                                <Button
                                    as-child
                                    size="sm"
                                    variant="outline"
                                    @click.stop
                                >
                                    <a
                                        :href="
                                            resumeEditor.assistant.url(
                                                resume.id,
                                            )
                                        "
                                    >
                                        <Sparkles class="mr-1 h-4 w-4" />
                                        Build with AI
                                    </a>
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click.stop="startRename(resume)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click.stop="duplicateResume(resume)"
                                >
                                    <Copy class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click.stop="deleteResume(resume)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card
                    v-if="selectedResume"
                    class="order-1 h-fit border-[#051C2E] bg-[#051C2E] text-white shadow-md lg:sticky lg:top-6"
                >
                    <CardHeader>
                        <CardTitle class="text-[18px] font-semibold"
                            >Resume Completeness</CardTitle
                        >
                        <CardDescription>
                            Selected Resume: {{ selectedResume.title }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div>
                            <div
                                class="flex items-center justify-between font-bold"
                            >
                                <span>Resume Completeness</span>
                                <span class="text-xl text-sky-200"
                                    >{{ selectedResume.completeness }}%</span
                                >
                            </div>
                            <div
                                class="mt-2 h-2 overflow-hidden rounded-full bg-white/15"
                            >
                                <div
                                    class="h-full rounded-full bg-blue-400"
                                    :style="{
                                        width: `${selectedResume.completeness}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-white p-4 text-[#14213D]">
                            <h3 class="mb-3 text-[15px] font-semibold">
                                Resume Insights
                            </h3>
                            <div class="space-y-2 text-[13px]">
                                <div
                                    v-for="item in selectedResume.completeness_items"
                                    :key="item.label"
                                    class="flex items-center gap-2"
                                >
                                    <CheckCircle2
                                        v-if="item.complete"
                                        class="h-4 w-4 text-emerald-600"
                                    />
                                    <Circle
                                        v-else
                                        class="h-4 w-4 text-amber-600"
                                    />
                                    <span
                                        :class="
                                            !item.complete && 'font-semibold'
                                        "
                                    >
                                        {{
                                            item.complete
                                                ? item.label
                                                : `Add ${item.label.toLowerCase()}`
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Button as-child>
                                <a
                                    :href="
                                        resumeEditor.assistant.url(
                                            selectedResume.id,
                                        )
                                    "
                                >
                                    <Sparkles class="mr-2 h-4 w-4" />Improve
                                    with AI
                                </a>
                            </Button>
                            <Button as-child variant="outline">
                                <a
                                    :href="
                                        resumeEditor.show.url(selectedResume.id)
                                    "
                                >
                                    <Eye class="mr-2 h-4 w-4" />View Resume
                                </a>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
