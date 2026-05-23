<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BadgeCheck, MapPin, Heart, ChevronLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getApplicationStatusColor } from '@/helpers/job-applications';
import { stringForHuman } from '@/helpers/strings';
import { jobSelection as jobSelectionRoute } from '@/routes';
import { apply as jobSelectionApply } from '@/routes/job-selection';
import type { WorkJob } from '@/types/laravel-models';
import type { UserWorkJobApplication } from '@/types/laravel-models';

const props = defineProps<{
    job: WorkJob;
    userApplication: UserWorkJobApplication | null;
}>();

const isLoading = ref(false);

const handleApply = () => {
    isLoading.value = true;
    router.post(
        jobSelectionApply(props.job.id),
        {},
        {
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
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

    <div class="flex h-full flex-1 flex-col bg-white dark:bg-stone-900">
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
                            There are currently 5 people considering this
                            position
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

                        <div
                            class="mb-6 text-lg font-medium text-stone-600 dark:text-stone-300"
                        >
                            from ${{
                                Number(job.salary_start).toLocaleString()
                            }}/month
                        </div>
                    </div>

                    <!-- Heart Icon -->
                    <button
                        class="ml-4 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blueish/60 transition-colors hover:bg-blueish dark:bg-stone-800 dark:hover:bg-stone-700"
                    >
                        <Heart class="h-6 w-6 text-stone-400" />
                    </button>
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
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        <p
                            class="leading-relaxed whitespace-pre-wrap text-stone-600 dark:text-stone-300"
                        >
                            {{ job.description }}
                        </p>
                    </div>
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
                    </template>
                    <template v-else>
                        <Button
                            @click="handleApply"
                            :disabled="isLoading"
                            class="flex-1 rounded-lg bg-primary px-8 py-6 text-base font-semibold tracking-wide text-primary-foreground hover:bg-primary/90"
                        >
                            {{
                                isLoading ? 'Applying...' : 'Apply for Position'
                            }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
