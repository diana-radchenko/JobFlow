<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowUpRight, BookOpen, Clock, Heart, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Category =
    | 'All'
    | 'Job Search'
    | 'Career Skills'
    | 'Labor Market'
    | 'AI & Hiring';

const resources = ref([
    {
        id: 'future-of-jobs-2025',
        title: 'The Future of Jobs Report 2025',
        description:
            'Global labor-market trends, growing roles and the skills employers expect to need.',
        category: 'Labor Market' as Category,
        source: 'World Economic Forum',
        readingMinutes: 20,
        brand: 'WEF',
        brandClass: 'bg-[#0B315B] text-white',
        url: 'https://www.weforum.org/publications/the-future-of-jobs-report-2025/',
        favorite: false,
    },
    {
        id: 'aarp-fields-over-50',
        title: '10 Promising Job Fields for Workers Over 50',
        description:
            'A practical overview of fields offering opportunities to experienced workers.',
        category: 'Job Search' as Category,
        source: 'AARP',
        readingMinutes: 12,
        brand: 'AARP',
        brandClass: 'bg-red-50 text-red-800',
        url: 'https://www.aarp.org/work/job-search/in-demand-job-fields-workers-over-50/',
        favorite: false,
    },
    {
        id: 'proximus-freelance',
        title: 'Why freelance at Proximus?',
        description:
            'Explore current freelance opportunities and flexible project work at Proximus.',
        category: 'Job Search' as Category,
        source: 'Proximus',
        readingMinutes: 5,
        brand: 'PROXIMUS',
        brandClass: 'bg-blue-50 text-[#0B315B]',
        url: 'https://proximus.talent-pool.com/freelance',
        favorite: false,
    },
    {
        id: 'emotional-intelligence',
        title: 'IQ or EI: You Need Both',
        description:
            'Why emotional intelligence and cognitive ability both matter at work.',
        category: 'Career Skills' as Category,
        source: 'Daniel Goleman',
        readingMinutes: 7,
        brand: 'EI',
        brandClass: 'bg-amber-50 text-amber-900',
        url: 'https://danielgolemanemotionalintelligence.com/iq-or-ei-you-need-both/',
        favorite: false,
    },
    {
        id: 'ai-screening-hbr',
        title: 'How to Get Hired When AI Does the Screening',
        description:
            'Practical guidance on preparing for AI-assisted resume screening and modern hiring processes.',
        category: 'AI & Hiring' as Category,
        source: 'Harvard Business Review',
        readingMinutes: 6,
        brand: 'HBR',
        brandClass: 'bg-slate-950 text-white',
        url: 'https://hbr.org/2025/02/how-to-get-hired-when-ai-does-the-screening',
        favorite: false,
    },
    {
        id: 'skills-on-the-rise-2025',
        title: 'Skills on the Rise in 2025',
        description:
            'A data-driven overview of fast-growing skills professionals can develop for the changing labor market.',
        category: 'Career Skills' as Category,
        source: 'LinkedIn',
        readingMinutes: 5,
        brand: 'in',
        brandClass: 'bg-[#0A66C2] text-white',
        url: 'https://www.linkedin.com/business/talent/blog/learning-and-development/skills-on-the-rise',
        favorite: false,
    },
]);

const categories: Category[] = [
    'All',
    'Job Search',
    'Career Skills',
    'Labor Market',
    'AI & Hiring',
];
const search = ref('');
const category = ref<Category>('All');
const sort = ref<'title' | 'shortest' | 'longest'>('title');
const favoritesOnly = ref(false);
const visibleResources = computed(() => {
    const query = search.value.trim().toLowerCase();

    return resources.value
        .filter(
            (item) =>
                category.value === 'All' || item.category === category.value,
        )
        .filter((item) => !favoritesOnly.value || item.favorite)
        .filter(
            (item) =>
                !query ||
                `${item.title} ${item.description} ${item.category} ${item.source}`
                    .toLowerCase()
                    .includes(query),
        )
        .sort((a, b) =>
            sort.value === 'shortest'
                ? a.readingMinutes - b.readingMinutes
                : sort.value === 'longest'
                  ? b.readingMinutes - a.readingMinutes
                  : a.title.localeCompare(b.title),
        );
});

defineOptions({
    layout: { breadcrumbs: [{ title: 'Development', href: '/development' }] },
});
</script>

<template>
    <Head title="Career Development" />
    <div class="jobflow-page dark:bg-slate-950">
        <div class="jobflow-page-frame">
            <section>
                <p
                    class="text-sm font-bold tracking-wide text-primary uppercase"
                >
                    Recommended for You
                </p>
                <h1 class="jobflow-page-title mt-1">Career Development</h1>
                <p class="mt-2 text-slate-500">
                    Practical resources for your job search and long-term career
                    skills.
                </p>
            </section>
            <Card
                ><CardContent
                    class="grid gap-3 p-5 lg:grid-cols-[minmax(0,1fr)_auto_auto]"
                >
                    <div class="relative">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                        /><Input
                            v-model="search"
                            class="pl-9"
                            placeholder="Search career resources"
                        />
                    </div>
                    <select
                        v-model="sort"
                        class="resource-select"
                        aria-label="Sort resources"
                    >
                        <option value="title">Sort by title</option>
                        <option value="shortest">Shortest first</option>
                        <option value="longest">Longest first</option>
                    </select>
                    <Button
                        type="button"
                        :variant="favoritesOnly ? 'default' : 'outline'"
                        @click="favoritesOnly = !favoritesOnly"
                        ><Heart class="mr-2 h-4 w-4" />Favorites only</Button
                    >
                </CardContent></Card
            >
            <div class="flex flex-wrap gap-2" aria-label="Resource categories">
                <Button
                    v-for="item in categories"
                    :key="item"
                    type="button"
                    size="sm"
                    :variant="category === item ? 'default' : 'outline'"
                    @click="category = item"
                    >{{ item }}</Button
                >
            </div>
            <section>
                <div class="mb-4 flex items-center gap-2">
                    <BookOpen class="h-6 w-6 text-primary" />
                    <h2 class="jobflow-section-title">Career Resources</h2>
                </div>
                <div
                    v-if="visibleResources.length"
                    class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
                >
                    <Card
                        v-for="resource in visibleResources"
                        :key="resource.id"
                        class="overflow-hidden border-slate-200/70 bg-white py-0 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/25 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            class="flex h-44 flex-col items-center justify-center border-b border-black/5 px-6 text-center"
                            :class="resource.brandClass"
                            :aria-label="`${resource.source} article`"
                        >
                            <span class="text-3xl font-black tracking-tight">{{
                                resource.brand
                            }}</span>
                            <span
                                class="mt-2 text-xs font-bold tracking-[0.18em] uppercase opacity-75"
                                >{{ resource.source }}</span
                            >
                        </div>
                        <CardContent class="space-y-3 p-5">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <Badge variant="secondary">{{
                                    resource.category
                                }}</Badge
                                ><span
                                    class="flex items-center gap-1 text-sm text-slate-500"
                                    ><Clock class="h-3.5 w-3.5" />{{
                                        resource.readingMinutes
                                    }}
                                    min</span
                                >
                            </div>
                            <h3 class="text-lg font-bold">
                                {{ resource.title }}
                            </h3>
                            <p class="text-base leading-relaxed text-slate-600">
                                {{ resource.description }}
                            </p>
                            <p class="text-sm font-semibold text-slate-500">
                                {{ resource.source }}
                            </p>
                            <div class="flex items-center justify-between">
                                <Button as-child size="sm"
                                    ><a
                                        :href="resource.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        >Read article<ArrowUpRight
                                            class="ml-1.5 h-4 w-4" /></a></Button
                                ><Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="
                                        resource.favorite
                                            ? 'Remove favorite'
                                            : 'Add favorite'
                                    "
                                    @click="
                                        resource.favorite = !resource.favorite
                                    "
                                    ><Heart
                                        class="h-4 w-4"
                                        :class="
                                            resource.favorite &&
                                            'fill-primary text-primary'
                                        "
                                /></Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <div
                    v-else
                    class="rounded-2xl border border-dashed p-10 text-center"
                >
                    <p class="font-semibold">
                        No career resources match these filters.
                    </p>
                    <Button
                        class="mt-3"
                        variant="outline"
                        @click="
                            search = '';
                            category = 'All';
                            favoritesOnly = false;
                        "
                        >Clear filters</Button
                    >
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.resource-select {
    border-radius: 0.375rem;
    border-width: 1px;
    background: var(--background);
    padding: 0.5rem 0.75rem;
}
</style>
