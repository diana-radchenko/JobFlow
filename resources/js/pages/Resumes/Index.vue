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
import { computed, ref, watch } from 'vue';
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
const initialSelectedResume =
    props.resumes.find((resume) => resume.is_primary) ?? props.resumes[0] ?? null;
const selectedResumeId = ref<string | null>(
    initialSelectedResume ? normalizeResumeId(initialSelectedResume.id) : null,
);
const isSelectedResume = (resume: ResumeSummary) =>
    selectedResumeId.value === normalizeResumeId(resume.id);
const selectResume = (resume: ResumeSummary) => {
    selectedResumeId.value = normalizeResumeId(resume.id);
};
const selectedResume = computed(
    () => props.resumes.find((resume) => isSelectedResume(resume)) ?? null,
);
const primaryResume = computed(
    () => props.resumes.find((resume) => resume.is_primary) ?? null,
);
const nonPrimaryResumes = computed(() =>
    props.resumes.filter((resume) => !resume.is_primary),
);

watch(
    () => props.resumes.map((resume) => normalizeResumeId(resume.id)),
    () => {
        if (
            selectedResumeId.value &&
            props.resumes.some((resume) => isSelectedResume(resume))
        ) {
            return;
        }

        const fallback =
            props.resumes.find((resume) => resume.is_primary) ??
            props.resumes[0] ??
            null;
        selectedResumeId.value = fallback
            ? normalizeResumeId(fallback.id)
            : null;
    },
);

const setPrimaryResume = (resume: ResumeSummary) => {
    router.post(
        '/resumes/' + resume.id + '/primary',
        {},
        { preserveScroll: true },
    );
};

const selectedRecommendation = computed(() => {
    if (!selectedResume.value) {
        return '';
    }

    const missingItem = selectedResume.value.completeness_items.find(
        (item) => !item.complete,
    );

    if (!missingItem) {
        return 'Your resume is complete. Keep it updated as your experience grows.';
    }

    return (
        'Add ' +
        missingItem.label.toLowerCase() +
        ' to strengthen your profile and improve your chances of getting more matches.'
    );
});

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
        <div class="jobflow-page-frame space-y-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="jobflow-page-title">Resume Center</h1>
                    <p class="text-sm text-foreground/60">
                        Manage different resumes for different opportunities.
                    </p>
                </div>
                <Button
                    v-if="!showCreateForm"
                    type="button"
                    class="bg-[#051C2E] text-white hover:bg-[#0A2E48]"
                    @click="showCreateForm = true"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    New Resume
                </Button>
            </div>

            <Card v-if="showCreateForm">
                <CardContent class="pt-6">
                    <form
                        class="flex flex-col gap-3 sm:flex-row sm:items-end"
                        @submit.prevent="submitCreate"
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
                                placeholder="e.g., Frontend Developer"
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
                class="rounded-2xl border border-dashed border-slate-300 bg-white py-12 text-center"
            >
                <FileText class="mx-auto mb-3 h-10 w-10 text-foreground/30" />
                <p class="text-foreground/60">
                    Create your first resume to start receiving tailored
                    recommendations.
                </p>
            </div>

            <section v-if="primaryResume" class="space-y-3">
                <h2
                    class="text-sm font-bold tracking-[0.08em] text-[#051C2E] uppercase"
                >
                    Primary Resume
                </h2>

                <Card
                    class="cursor-pointer border-[#0A2E48]/40 bg-white shadow-sm transition-all duration-200 hover:border-[#0A2E48]/70 hover:shadow-md"
                    :class="{
                        'border-[#0A2E48] ring-2 ring-[#0A2E48]/20':
                            isSelectedResume(primaryResume),
                    }"
                    @click="selectResume(primaryResume)"
                >
                    <CardContent class="p-5 sm:p-6">
                        <form
                            v-if="editingId === primaryResume.id"
                            class="flex gap-2"
                            @submit.prevent="submitRename(primaryResume)"
                            @click.stop
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

                        <div
                            v-else
                            class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,1.2fr)_auto] xl:items-center"
                        >
                            <div class="flex min-w-0 items-start gap-4">
                                <div
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#051C2E] text-white"
                                >
                                    <FileText class="h-7 w-7" />
                                </div>
                                <div class="min-w-0">
                                    <h3
                                        class="text-xl leading-tight font-semibold text-[#051C2E]"
                                    >
                                        {{ primaryResume.title }}
                                    </h3>
                                    <p class="mt-1 text-sm text-[#667085]">
                                        Updated
                                        {{
                                            formatDate(primaryResume.updated_at)
                                        }}
                                    </p>
                                    <div
                                        class="mt-3 flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="rounded-full bg-[#051C2E] px-3 py-1 text-[11px] font-bold tracking-wide text-white"
                                        >
                                            PRIMARY RESUME
                                        </span>
                                        <span
                                            v-if="
                                                isSelectedResume(primaryResume)
                                            "
                                            class="rounded-full border border-[#0A2E48]/35 bg-white px-2.5 py-1 text-[11px] font-semibold text-[#0A2E48]"
                                        >
                                            Selected
                                        </span>
                                    </div>
                                    <p
                                        class="mt-3 max-w-md text-[13px] leading-relaxed text-[#475467]"
                                    >
                                        Used by JobFlow by default for job
                                        matching and recommendations.
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <span
                                        class="text-sm font-semibold text-[#344054]"
                                    >
                                        Resume Completeness
                                    </span>
                                    <span
                                        class="text-lg font-bold text-[#051C2E]"
                                    >
                                        {{ primaryResume.completeness }}%
                                        Complete
                                    </span>
                                </div>
                                <div
                                    class="h-2 overflow-hidden rounded-full bg-slate-200"
                                >
                                    <div
                                        class="h-full rounded-full bg-[#0A2E48]"
                                        :style="{
                                            width:
                                                primaryResume.completeness + '%',
                                        }"
                                    ></div>
                                </div>
                                <div
                                    class="grid gap-2 text-[13px] sm:grid-cols-2"
                                >
                                    <span
                                        v-for="item in primaryResume.completeness_items.slice(
                                            0,
                                            6,
                                        )"
                                        :key="item.label"
                                        class="flex items-center gap-2"
                                        :class="
                                            item.complete
                                                ? 'text-emerald-700'
                                                : 'text-[#667085]'
                                        "
                                    >
                                        <CheckCircle2
                                            v-if="item.complete"
                                            class="h-4 w-4"
                                        />
                                        <Circle v-else class="h-4 w-4" />
                                        {{ item.label }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap gap-2 xl:w-44 xl:flex-col"
                            >
                                <Button
                                    as-child
                                    size="sm"
                                    variant="outline"
                                    @click.stop
                                >
                                    <a
                                        :href="
                                            resumeEditor.show.url(
                                                primaryResume.id,
                                            )
                                        "
                                    >
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Edit
                                    </a>
                                </Button>
                                <Button
                                    as-child
                                    size="sm"
                                    class="bg-[#051C2E] text-white hover:bg-[#0A2E48]"
                                    @click.stop
                                >
                                    <a
                                        :href="
                                            resumeEditor.assistant.url(
                                                primaryResume.id,
                                            )
                                        "
                                    >
                                        <Sparkles class="mr-2 h-4 w-4" />
                                        Build with AI
                                    </a>
                                </Button>
                                <div class="flex gap-1">
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        aria-label="Rename resume"
                                        @click.stop="
                                            startRename(primaryResume)
                                        "
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        aria-label="Duplicate resume"
                                        @click.stop="
                                            duplicateResume(primaryResume)
                                        "
                                    >
                                        <Copy class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        aria-label="Delete resume"
                                        @click.stop="
                                            deleteResume(primaryResume)
                                        "
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section v-if="resumes.length > 0" class="space-y-3">
                <div>
                    <h2
                        class="text-sm font-bold tracking-[0.08em] text-[#051C2E] uppercase"
                    >
                        Your Resumes
                    </h2>
                    <p class="mt-1 text-sm text-[#667085]">
                        Select a resume to view its insights or set it as your
                        primary resume.
                    </p>
                </div>

                <div
                    v-if="nonPrimaryResumes.length > 0"
                    class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3"
                >
                    <Card
                        v-for="resume in nonPrimaryResumes"
                        :key="resume.id"
                        class="cursor-pointer border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#0A2E48]/40 hover:shadow-md"
                        :class="{
                            'border-[#0A2E48] ring-2 ring-[#0A2E48]/20':
                                isSelectedResume(resume),
                        }"
                        @click="selectResume(resume)"
                    >
                        <CardHeader class="pb-3">
                            <form
                                v-if="editingId === resume.id"
                                class="flex gap-2"
                                @submit.prevent="submitRename(resume)"
                                @click.stop
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
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <CardTitle
                                            class="text-[16px] leading-snug font-semibold text-[#051C2E]"
                                        >
                                            {{ resume.title }}
                                        </CardTitle>
                                        <CardDescription class="mt-1 text-sm">
                                            Updated
                                            {{ formatDate(resume.updated_at) }}
                                        </CardDescription>
                                    </div>
                                    <span
                                        v-if="isSelectedResume(resume)"
                                        class="shrink-0 rounded-full border border-[#0A2E48]/35 bg-white px-2.5 py-1 text-[11px] font-semibold text-[#0A2E48]"
                                    >
                                        Selected
                                    </span>
                                </div>
                            </template>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="text-sm text-[#667085]">
                                        Resume Completeness
                                    </span>
                                    <span class="font-bold text-[#051C2E]">
                                        {{ resume.completeness }}% Complete
                                    </span>
                                </div>
                                <div
                                    class="h-1.5 overflow-hidden rounded-full bg-slate-200"
                                >
                                    <div
                                        class="h-full rounded-full bg-[#0A2E48]"
                                        :style="{
                                            width: resume.completeness + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div
                                class="grid gap-2 text-[12.5px] sm:grid-cols-2"
                            >
                                <span
                                    v-for="item in resume.completeness_items.slice(
                                        0,
                                        4,
                                    )"
                                    :key="item.label"
                                    class="flex items-center gap-2"
                                    :class="
                                        item.complete
                                            ? 'text-emerald-700'
                                            : 'text-[#667085]'
                                    "
                                >
                                    <CheckCircle2
                                        v-if="item.complete"
                                        class="h-4 w-4"
                                    />
                                    <Circle v-else class="h-4 w-4" />
                                    {{ item.label }}
                                </span>
                            </div>

                            <div
                                class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-[#667085]"
                            >
                                <span>
                                    {{ resume.work_experiences_count }}
                                    experience
                                </span>
                                <span>
                                    {{ resume.educations_count }} education
                                </span>
                                <span>{{ resume.skills_count }} skills</span>
                                <span>{{ resume.projects_count }} projects</span>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="border-[#0A2E48] text-[#0A2E48] hover:bg-[#F3F6F9]"
                                    @click.stop="setPrimaryResume(resume)"
                                >
                                    Set as Primary
                                </Button>
                                <Button
                                    as-child
                                    size="sm"
                                    variant="outline"
                                    @click.stop
                                >
                                    <a
                                        :href="
                                            resumeEditor.show.url(resume.id)
                                        "
                                    >
                                        <Pencil class="mr-1 h-4 w-4" />
                                        Edit
                                    </a>
                                </Button>
                                <Button
                                    as-child
                                    size="sm"
                                    class="bg-[#051C2E] text-white hover:bg-[#0A2E48]"
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
                            </div>

                            <div
                                class="flex gap-1 border-t border-slate-100 pt-2"
                            >
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    aria-label="Rename resume"
                                    @click.stop="startRename(resume)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    aria-label="Duplicate resume"
                                    @click.stop="duplicateResume(resume)"
                                >
                                    <Copy class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    aria-label="Delete resume"
                                    @click.stop="deleteResume(resume)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-6 text-sm text-[#667085]"
                >
                    Your primary resume is currently your only resume.
                </div>
            </section>

            <section v-if="selectedResume" class="space-y-3">
                <div>
                    <h2
                        class="text-sm font-bold tracking-[0.08em] text-[#051C2E] uppercase"
                    >
                        Resume Insights — {{ selectedResume.title }}
                    </h2>
                    <p class="mt-1 text-sm text-[#667085]">
                        See what's complete, what's missing, and what to improve
                        next.
                    </p>
                </div>

                <Card class="border-slate-200 bg-white shadow-sm">
                    <CardContent
                        class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[180px_minmax(0,1fr)_minmax(240px,0.65fr)] lg:items-start"
                    >
                        <div class="space-y-3">
                            <div class="text-4xl font-black text-[#051C2E]">
                                {{ selectedResume.completeness }}%
                            </div>
                            <div class="text-sm font-semibold text-[#344054]">
                                Ready
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-slate-200"
                            >
                                <div
                                    class="h-full rounded-full bg-[#0A2E48]"
                                    :style="{
                                        width:
                                            selectedResume.completeness + '%',
                                    }"
                                ></div>
                            </div>
                        </div>

                        <div
                            class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2"
                        >
                            <div
                                v-for="item in selectedResume.completeness_items"
                                :key="item.label"
                                class="flex items-center gap-2"
                            >
                                <CheckCircle2
                                    v-if="item.complete"
                                    class="h-4 w-4 shrink-0 text-emerald-600"
                                />
                                <Circle
                                    v-else
                                    class="h-4 w-4 shrink-0 text-amber-600"
                                />
                                <span
                                    :class="
                                        item.complete
                                            ? 'text-[#344054]'
                                            : 'font-semibold text-[#92400E]'
                                    "
                                >
                                    {{
                                        item.complete
                                            ? item.label
                                            : 'Add ' +
                                              item.label.toLowerCase()
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div
                                class="rounded-xl border border-[#D8E6F3] bg-[#F3F7FB] p-4"
                            >
                                <h3
                                    class="text-sm font-semibold text-[#051C2E]"
                                >
                                    Recommendation
                                </h3>
                                <p
                                    class="mt-2 text-sm leading-relaxed text-[#475467]"
                                >
                                    {{ selectedRecommendation }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Button
                                    as-child
                                    size="sm"
                                    class="bg-[#051C2E] text-white hover:bg-[#0A2E48]"
                                >
                                    <a
                                        :href="
                                            resumeEditor.assistant.url(
                                                selectedResume.id,
                                            )
                                        "
                                    >
                                        <Sparkles class="mr-2 h-4 w-4" />
                                        Improve with AI
                                    </a>
                                </Button>
                                <Button
                                    as-child
                                    size="sm"
                                    variant="outline"
                                >
                                    <a
                                        :href="
                                            resumeEditor.show.url(
                                                selectedResume.id,
                                            )
                                        "
                                    >
                                        <Eye class="mr-2 h-4 w-4" />
                                        View Resume
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </div>
</template>
