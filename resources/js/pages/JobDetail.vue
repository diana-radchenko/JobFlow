<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { BadgeCheck, MapPin, Heart, ChevronLeft } from 'lucide-vue-next';
import { marked } from 'marked';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getApplicationStatusColor } from '@/helpers/job-applications';
import { stringForHuman } from '@/helpers/strings';
import type { WorkJob } from '@/types/laravel-models';
import type { UserWorkJobApplication } from '@/types/laravel-models';
import { store as sendJobMessageRoute } from '@/actions/App/Http/Controllers/JobChatController';
import { jobSelection as jobSelectionRoute } from '@/routes';
import { apply as jobSelectionApply } from '@/routes/job-selection';

const props = defineProps<{
    job: WorkJob;
    userApplication: UserWorkJobApplication | null;
    resumes: { id: number; title: string }[];
    saved: boolean;
}>();

marked.setOptions({ gfm: true, breaks: true });

/** Job descriptions are authored as Markdown; render as sanitized HTML. */
const descriptionHtml = DOMPurify.sanitize(
    marked.parse(props.job.description, { async: false }) as string,
);
const sectionHtml = (value?: string | null) =>
    value
        ? DOMPurify.sanitize(marked.parse(value, { async: false }) as string)
        : '';

const salary = () => {
    if (props.job.salary_start === null && props.job.salary_end === null) {
        return 'Salary not specified';
    }

    const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.job.salary_currency || 'USD',
        maximumFractionDigits: 0,
    });
    const values = [props.job.salary_start, props.job.salary_end]
        .filter((value) => value !== null)
        .map((value) => formatter.format(Number(value)));

    return `${values.join('–')}${props.job.salary_period ? ` / ${stringForHuman(props.job.salary_period)}` : ''}`;
};

const toggleSaved = () => {
    if (props.saved) {
        router.delete(`/saved-jobs/${props.job.id}`, { preserveScroll: true });
    } else {
        router.post(
            `/saved-jobs/${props.job.id}`,
            {},
            { preserveScroll: true },
        );
    }
};

const isLoading = ref(false);
const resumeId = ref<string>(
    props.resumes.length > 0 ? String(props.resumes[0].id) : '',
);

const handleApply = () => {
    if (!resumeId.value) {
        return;
    }

    isLoading.value = true;
    router.post(
        jobSelectionApply(props.job.id),
        { resume_id: resumeId.value },
        {
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};
const messageForm = useForm({ body: '' });
const sendMessage = () => {
    if (!props.userApplication) {
        return;
    }

    messageForm.post(sendJobMessageRoute(props.userApplication.id).url, {
        onSuccess: () => messageForm.reset(),
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Job Selection',
                href: jobSelectionRoute(),
            },
            {
                title: 'Job Details',
            },
        ],
    },
});
</script>

<template>
    <Head :title="job.title" />

    <div
        class="flex h-full flex-1 flex-col bg-[#F5F7FB] text-[#14213D] dark:bg-stone-900"
    >
        <!-- Back Button -->
        <div class="border-b border-stone-200 dark:border-stone-800">
            <div class="flex items-center gap-2 p-4 md:p-6">
                <Link
                    :href="jobSelectionRoute.url()"
                    class="flex items-center gap-2 text-stone-600 transition-colors hover:text-stone-900 dark:text-stone-400 dark:hover:text-white"
                >
                    <ChevronLeft class="h-5 w-5" />
                    <span>Back to Jobs</span>
                </Link>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-4 md:p-6">
            <div class="mx-auto max-w-3xl">
                <!-- Header with Heart Icon -->
                <div class="mb-6 flex items-start justify-between">
                    <div class="flex-1">
                        <div class="mb-2 text-sm text-stone-500">
                            {{ job.applications_count }} applicant{{
                                job.applications_count === 1 ? '' : 's'
                            }}
                            for this position
                        </div>

                        <h1
                            class="mb-4 text-4xl font-bold text-stone-900 dark:text-white"
                        >
                            {{ job.title }}
                        </h1>

                        <div class="mb-4 flex items-center gap-1.5">
                            <span
                                class="text-lg font-bold text-stone-900 dark:text-white"
                                >{{ job.company }}</span
                            >
                            <BadgeCheck
                                class="h-6 w-6 fill-stone-900 stroke-white text-stone-900 dark:fill-white dark:stroke-stone-900 dark:text-white"
                            />
                        </div>

                        <div
                            class="mb-4 flex items-center gap-1.5 text-stone-600 dark:text-stone-300"
                        >
                            <MapPin class="h-5 w-5" />
                            <span class="text-lg">{{ job.location }}</span>
                        </div>

                        <div class="mb-3 flex flex-wrap gap-2">
                            <Badge
                                v-if="job.workplace_type"
                                variant="secondary"
                                >{{ job.workplace_type }}</Badge
                            >
                            <Badge v-if="job.industry" variant="outline">{{
                                job.industry
                            }}</Badge>
                            <Badge
                                v-if="job.position_level"
                                variant="outline"
                                >{{ job.position_level }}</Badge
                            >
                            <Badge
                                v-if="job.employment_type"
                                variant="outline"
                                >{{ job.employment_type }}</Badge
                            >
                        </div>

                        <div
                            class="mb-6 text-lg font-medium text-stone-600 dark:text-stone-300"
                        >
                            {{ salary() }}
                        </div>
                    </div>

                    <!-- Heart Icon -->
                    <button
                        type="button"
                        :aria-label="
                            saved ? 'Remove saved vacancy' : 'Save vacancy'
                        "
                        class="ml-4 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blueish/60 transition-colors hover:bg-blueish dark:bg-stone-800 dark:hover:bg-stone-700"
                        @click="toggleSaved"
                    >
                        <Heart
                            class="h-6 w-6"
                            :class="
                                saved
                                    ? 'fill-primary text-primary'
                                    : 'text-stone-400'
                            "
                        />
                    </button>
                </div>

                <div v-if="job.responsibilities" class="mb-8">
                    <h2 class="mb-3 text-lg font-semibold">Responsibilities</h2>
                    <div
                        class="markdown-body text-stone-600 dark:text-stone-300"
                        v-html="sectionHtml(job.responsibilities)"
                    />
                </div>
                <div v-if="job.requirements" class="mb-8">
                    <h2 class="mb-3 text-lg font-semibold">Requirements</h2>
                    <div
                        class="markdown-body text-stone-600 dark:text-stone-300"
                        v-html="sectionHtml(job.requirements)"
                    />
                </div>
                <div v-if="job.benefits" class="mb-8">
                    <h2 class="mb-3 text-lg font-semibold">Benefits</h2>
                    <div
                        class="markdown-body text-stone-600 dark:text-stone-300"
                        v-html="sectionHtml(job.benefits)"
                    />
                </div>

                <!-- Technologies -->
                <div class="mb-8">
                    <h2
                        class="mb-3 text-lg font-semibold text-stone-900 dark:text-white"
                    >
                        Technologies
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="(tech, index) in job.technologies"
                            :key="index"
                            variant="secondary"
                            class="rounded-full border-none bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            {{ tech }}
                        </Badge>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-8">
                    <h2
                        class="mb-3 text-lg font-semibold text-stone-900 dark:text-white"
                    >
                        Job Description
                    </h2>
                    <div
                        class="markdown-body max-w-none text-stone-600 dark:text-stone-300"
                        v-html="descriptionHtml"
                    />
                </div>

                <!-- Contacts -->
                <div v-if="job.contacts" class="mb-8">
                    <h2
                        class="mb-3 text-lg font-semibold text-stone-900 dark:text-white"
                    >
                        Contact Information
                    </h2>
                    <p
                        class="whitespace-pre-wrap text-stone-600 dark:text-stone-300"
                    >
                        {{ job.contacts }}
                    </p>
                </div>

                <!-- Apply Button -->
                <div
                    class="sticky bottom-0 mt-8 flex gap-4 border-t border-stone-200 bg-white pt-6 dark:border-stone-800 dark:bg-stone-900"
                >
                    <template v-if="userApplication">
                        <Button
                            disabled
                            :class="`flex-1 rounded-lg px-8 py-6 text-base font-semibold tracking-wide ${getApplicationStatusColor(userApplication.status)}`"
                        >
                            {{ stringForHuman(userApplication.status) }}
                        </Button>
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg px-8 py-6"
                        >
                            <Link href="/request-tracker"
                                >Track Application</Link
                            >
                        </Button>
                    </template>
                    <template v-else>
                        <div class="flex flex-1 flex-col gap-2">
                            <h2
                                class="text-lg font-semibold text-stone-900 dark:text-white"
                            >
                                Resume to apply
                            </h2>
                            <Select v-model="resumeId" :disabled="isLoading">
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select a resume to apply with"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="resume in resumes"
                                        :key="resume.id"
                                        :value="String(resume.id)"
                                    >
                                        {{ resume.title }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="resumes.length === 0"
                                class="text-sm text-stone-500"
                            >
                                You don't have any resumes yet.
                                <Link href="/resumes" class="underline"
                                    >Create one first</Link
                                >
                                to apply.
                            </p>
                            <Button
                                @click="handleApply"
                                :disabled="isLoading || !resumeId"
                                class="mt-6 rounded-lg bg-primary px-8 py-6 text-base font-semibold tracking-wide text-primary-foreground hover:bg-primary/90"
                            >
                                {{
                                    isLoading
                                        ? 'Applying...'
                                        : 'Apply for Position'
                                }}
                            </Button>
                        </div>
                    </template>
                </div>
                <form
                    v-if="userApplication"
                    class="mt-5 flex gap-2 rounded-xl border p-4"
                    @submit.prevent="sendMessage"
                >
                    <input
                        v-model="messageForm.body"
                        required
                        maxlength="5000"
                        class="flex-1 rounded-md border bg-background p-2"
                        placeholder="Message the employer about this application"
                    />
                    <Button type="submit" :disabled="messageForm.processing"
                        >Send message</Button
                    >
                </form>
            </div>
        </div>
    </div>
</template>
