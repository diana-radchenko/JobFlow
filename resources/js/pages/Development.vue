<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    SlidersHorizontal,
    ListFilter,
    Play,
    Pause,
    Heart,
    Clock,
    ChevronRight,
    ChevronLeft,
    Search,
    X,
    Volume2,
    Sparkles,
    BookOpen,
    HelpCircle,
    RotateCcw
} from 'lucide-vue-next';
import { ref, computed, onUnmounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter
} from '@/components/ui/dialog';

// Define breadcrumbs for Layout
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Development',
                href: '/development',
            },
        ],
    },
});

// Mock Audiobooks Data
const audiobooksData = ref([
    {
        id: 'ab-1',
        title: 'Atomic Habits',
        author: 'James Clear',
        description: 'A practical guide on how small changes can lead to remarkable results in personal and professional life.',
        duration: '5h 35m',
        durationMinutes: 335,
        image: 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&q=80&w=600',
        favorite: false
    },
    {
        id: 'ab-2',
        title: 'The Lean Startup',
        author: 'Eric Ries',
        description: 'An essential guide for entrepreneurs and business leaders on how to build and scale a successful startup.',
        duration: '8h 44m',
        durationMinutes: 524,
        image: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&q=80&w=600',
        favorite: false
    },
    {
        id: 'ab-3',
        title: 'Deep Work',
        author: 'Cal Newport',
        description: 'Learn strategies to enhance productivity, master difficult tasks, and create meaningful work.',
        duration: '7h 44m',
        durationMinutes: 464,
        image: 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&q=80&w=600',
        favorite: false
    },
    {
        id: 'ab-4',
        title: 'Grit: The Power of Passion and Perseverance',
        author: 'Angela Duckworth',
        description: 'Discover the key to outstanding achievement—not talent, but a blend of passion and persistence.',
        duration: '9h 22m',
        durationMinutes: 562,
        image: 'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?auto=format&fit=crop&q=80&w=600',
        favorite: false
    }
]);

// Mock Articles Data
const articlesData = ref([
    {
        id: 'ar-1',
        title: 'Future of Jobs Report',
        tags: ['Professional Development'],
        readingTime: '8 minutes',
        readingTimeMinutes: 8,
        image: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80&w=600',
        url: 'https://www.weforum.org/publications/the-future-of-jobs-report-2025/',
        favorite: false
    },
    {
        id: 'ar-2',
        title: 'Ready for a Career Change at 50? Expert Tips and Advice',
        tags: ['For Experienced Professionals & 50+ Workers'],
        readingTime: '10 minutes',
        readingTimeMinutes: 10,
        image: 'https://cdn.aarp.net/content/dam/aarpe/en/home/work/careers/50s-career-shift/_jcr_content/root/container_main/container_body_main/container_body1/container_body_cf/container_image/articlecontentfragment/cfimage.coreimg.50.932.jpeg/content/dam/aarp/work/work_at_plus/2023/04/1140-briefcase-of-professions.jpg',
        url: 'https://www.aarp.org/work/careers/50s-career-shift/',
        favorite: false
    },
    {
        id: 'ar-3',
        title: 'Job Fields That Will Be Seeking Workers Over 50',
        tags: ['For Experienced Professionals & 50+ Workers'],
        readingTime: '12 minutes',
        readingTimeMinutes: 12,
        image: 'https://cdn.aarp.net/content/dam/aarpe/en/home/work/job-search/in-demand-job-fields-workers-over-50/_jcr_content/root/container_main/container_body_main/container_image/articlecontentfragme/cfimage.coreimg.75.1440.jpeg/content/dam/aarp/work/job_hunting/2017/08/1140-jobs-for-people-over-50.jpg',
        url: 'https://www.aarp.org/work/job-search/in-demand-job-fields-workers-over-50/',
        favorite: false
    },
    {
        id: 'ar-4',
        title: '4 Ways to Meaningfully Support New Mothers Returning to Work',
        tags: ['For Parents Returning to Work'],
        readingTime: '7 minutes',
        readingTimeMinutes: 7,
        image: 'https://hbr.org/resources/images/article_assets/2024/07/Jul24_25_909336878.jpg',
        url: 'https://hbr.org/2024/07/4-ways-to-meaningfully-support-new-mothers-returning-to-work',
        favorite: false
    },
    {
        id: 'ar-5',
        title: 'Job Seekers Guide for Students and Graduates with Disabilities',
        tags: ['For Students & Recent Graduates', 'For Job Seekers with Disabilities'],
        readingTime: '7 minutes',
        readingTimeMinutes: 7,
        image: 'https://images.unsplash.com/photo-1577412647305-991150c7d163?auto=format&fit=crop&q=80&w=600',
        url: 'https://onleyinitiative.ca/wp-content/uploads/2020/07/DCOI-guidebook-Job-Seekers-Guide-for-Students-and-Graduates-with-Disabilities-ACC.pdf',
        favorite: false
    },
    {
        id: 'ar-6',
        title: 'IQ or EI: You Need Both',
        tags: ['Professional Development'],
        readingTime: '7 minutes',
        readingTimeMinutes: 7,
        image: 'https://danielgolemanemotionalintelligence.com/wp-content/uploads/2023/07/Post-Why-IQ-or-EI.png',
        url: 'https://danielgolemanemotionalintelligence.com/iq-or-ei-you-need-both/',
        favorite: false
    }
]);

// Search & Filter State
const searchQuery = ref('');
const showFiltersModal = ref(false);
const showSettingsModal = ref(false);

// Active filter preferences
const filterFavoriteOnly = ref(false);
const sortBy = ref<'none' | 'title' | 'duration_asc' | 'duration_desc'>('none');
const activeCategory = ref<'all' | 'audiobooks' | 'articles'>('all');

// Toast notifications state
const toastMessage = ref<string | null>(null);
let toastTimeout: NodeJS.Timeout | null = null;

function showToast(message: string) {
    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }
    toastMessage.value = message;
    toastTimeout = setTimeout(() => {
        toastMessage.value = null;
    }, 3000);
}

// Favorite toggle handlers
function toggleFavoriteAudiobook(id: string) {
    const book = audiobooksData.value.find(b => b.id === id);
    if (book) {
        book.favorite = !book.favorite;
        showToast(book.favorite ? `Added "${book.title}" to favorites!` : `Removed "${book.title}" from favorites.`);
    }
}

function toggleFavoriteArticle(id: string) {
    const article = articlesData.value.find(a => a.id === id);
    if (article) {
        article.favorite = !article.favorite;
        showToast(article.favorite ? `Added "${article.title}" to favorites!` : `Removed "${article.title}" from favorites.`);
    }
}

// Carousel element references & scrolling logic
const audiobooksContainer = ref<HTMLElement | null>(null);
const articlesContainer = ref<HTMLElement | null>(null);

function scroll(container: HTMLElement | null, direction: 'left' | 'right') {
    if (!container) return;
    const scrollAmount = 360; // Card width + gap
    container.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
}

// Audio Player Simulation State
interface PlayingTrack {
    id: string;
    title: string;
    author: string;
    image: string;
    duration: string;
    durationMinutes: number;
}

const playingTrack = ref<PlayingTrack | null>(null);
const isPlaying = ref(false);
const currentTime = ref(0); // in seconds
const playbackSpeed = ref(1); // multiplier
let playerInterval: NodeJS.Timeout | null = null;

function togglePlayAudiobook(book: typeof audiobooksData.value[0]) {
    if (playingTrack.value?.id === book.id) {
        // Toggle play/pause
        isPlaying.value = !isPlaying.value;
        if (isPlaying.value) {
            startPlaybackTimer();
            showToast(`Resumed playing "${book.title}"`);
        } else {
            stopPlaybackTimer();
            showToast(`Paused "${book.title}"`);
        }
    } else {
        // Start playing new book
        stopPlaybackTimer();
        playingTrack.value = {
            id: book.id,
            title: book.title,
            author: book.author,
            image: book.image,
            duration: book.duration,
            durationMinutes: book.durationMinutes
        };
        isPlaying.value = true;
        currentTime.value = 0;
        startPlaybackTimer();
        showToast(`Playing "${book.title}"`);
    }
}

function startPlaybackTimer() {
    if (playerInterval) clearInterval(playerInterval);
    playerInterval = setInterval(() => {
        if (playingTrack.value && isPlaying.value) {
            const totalSeconds = playingTrack.value.durationMinutes * 60;
            if (currentTime.value < totalSeconds) {
                currentTime.value += 1 * playbackSpeed.value;
            } else {
                currentTime.value = 0;
                isPlaying.value = false;
                stopPlaybackTimer();
                showToast(`Finished listening to "${playingTrack.value.title}"`);
            }
        }
    }, 1000);
}

function stopPlaybackTimer() {
    if (playerInterval) {
        clearInterval(playerInterval);
        playerInterval = null;
    }
}

function handleSeek(event: Event) {
    const target = event.target as HTMLInputElement;
    currentTime.value = parseInt(target.value);
}

// Formatter for elapsed player time
function formatPlayerTime(secondsCount: number): string {
    const h = Math.floor(secondsCount / 3600);
    const m = Math.floor((secondsCount % 3600) / 60);
    const s = Math.floor(secondsCount % 60);
    if (h > 0) {
        return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    return `${m}:${s.toString().padStart(2, '0')}`;
}

// Computed Filtered Lists
const filteredAudiobooks = computed(() => {
    let result = [...audiobooksData.value];

    // Search query filter
    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            b => b.title.toLowerCase().includes(query) || 
                 b.author.toLowerCase().includes(query) ||
                 b.description.toLowerCase().includes(query)
        );
    }

    // Favorite only filter
    if (filterFavoriteOnly.value) {
        result = result.filter(b => b.favorite);
    }

    // Sorting logic
    if (sortBy.value === 'title') {
        result.sort((a, b) => a.title.localeCompare(b.title));
    } else if (sortBy.value === 'duration_asc') {
        result.sort((a, b) => a.durationMinutes - b.durationMinutes);
    } else if (sortBy.value === 'duration_desc') {
        result.sort((a, b) => b.durationMinutes - a.durationMinutes);
    }

    return result;
});

const filteredArticles = computed(() => {
    let result = [...articlesData.value];

        // Search query filter
    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            a => a.title.toLowerCase().includes(query) || 
                 a.tags.some(tag => tag.toLowerCase().includes(query))
        );
    }

    // Favorite only filter
    if (filterFavoriteOnly.value) {
        result = result.filter(a => a.favorite);
    }

    // Sorting logic
    if (sortBy.value === 'title') {
        result.sort((a, b) => a.title.localeCompare(b.title));
    } else if (sortBy.value === 'duration_asc') {
        result.sort((a, b) => a.readingTimeMinutes - b.readingTimeMinutes);
    } else if (sortBy.value === 'duration_desc') {
        result.sort((a, b) => b.readingTimeMinutes - a.readingTimeMinutes);
    }

    return result;
});

// Reset all filters helper
function resetFilters() {
    searchQuery.value = '';
    filterFavoriteOnly.value = false;
    sortBy.value = 'none';
    activeCategory.value = 'all';
    showToast('Filters reset successfully');
}

onUnmounted(() => {
    stopPlaybackTimer();
});
</script>

<template>
    <Head title="Development Hub" />

    <div class="relative min-h-[calc(100vh-64px)] bg-[#f8fafc] dark:bg-slate-950 px-6 py-8 font-sans transition-colors duration-200">
        <!-- Floating Toast Alert -->
        <div 
            v-if="toastMessage" 
            class="fixed top-20 right-6 z-50 bg-slate-900 dark:bg-slate-800 text-white border border-slate-700/50 rounded-2xl shadow-xl px-5 py-3.5 flex items-center gap-3 animate-in fade-in slide-in-from-top-4 duration-300 max-w-sm"
        >
            <Sparkles class="h-5 w-5 text-amber-400 shrink-0" />
            <span class="text-sm font-bold tracking-tight">{{ toastMessage }}</span>
        </div>

        <div class="container mx-auto max-w-[1300px] space-y-10 pb-28">
            
            <!-- Beautiful Action Bar -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-4 rounded-3xl shadow-sm">
                <!-- Search Input with lucide icon -->
                <div class="relative flex-1">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 dark:text-slate-500" />
                    <Input 
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search audiobooks, articles or authors..."
                        class="pl-12 pr-4 py-3 h-11 bg-slate-50 dark:bg-slate-950 border-slate-200/60 dark:border-slate-800 rounded-2xl text-[15px] font-medium text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus-visible:ring-primary focus-visible:border-transparent transition-all duration-200 w-full"
                    />
                </div>

                <!-- Control Buttons -->
                <div class="flex items-center gap-2">
                    <!-- Filter Pill Button -->
                    <Button 
                        variant="outline" 
                        @click="showFiltersModal = true"
                        class="h-11 px-5 rounded-2xl border-slate-200/60 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 font-bold text-sm tracking-tight flex items-center gap-2 cursor-pointer"
                    >
                        <ListFilter class="h-4.5 w-4.5" />
                        <span>Filter & Sort</span>
                        <Badge v-if="filterFavoriteOnly || sortBy !== 'none'" variant="secondary" class="ml-1 h-5 px-1.5 min-w-5 flex items-center justify-center bg-primary text-primary-foreground font-black text-[10px] rounded-full">
                            {{ (filterFavoriteOnly ? 1 : 0) + (sortBy !== 'none' ? 1 : 0) }}
                        </Badge>
                    </Button>

                    <!-- Reset filters if dirty -->
                    <Button 
                        v-if="searchQuery || filterFavoriteOnly || sortBy !== 'none' || activeCategory !== 'all'"
                        variant="ghost" 
                        @click="resetFilters"
                        class="h-11 px-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white"
                        title="Reset all filters"
                    >
                        <RotateCcw class="h-4.5 w-4.5" />
                    </Button>
                </div>
            </div>

            <!-- AUDIOBOOKS SECTION -->
            <div 
                v-if="activeCategory === 'all' || activeCategory === 'audiobooks'" 
                class="space-y-4"
            >
                <!-- Section Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <BookOpen class="h-6 w-6 text-[#021F35] dark:text-blue-400" />
                        <h2 class="text-2xl font-extrabold tracking-tight text-[#021F35] dark:text-slate-50">Audiobooks</h2>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Navigation chevrons (horizontal scrolling control) -->
                        <button 
                            @click="scroll(audiobooksContainer, 'left')"
                            class="w-10 h-10 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition-all text-slate-600 dark:text-slate-300"
                        >
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <button 
                            @click="scroll(audiobooksContainer, 'right')"
                            class="w-10 h-10 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition-all text-slate-600 dark:text-slate-300"
                        >
                            <ChevronRight class="h-5 w-5" />
                        </button>

                        <!-- Circular buttons as in Image -->
                        <button 
                            @click="showFiltersModal = true"
                            class="w-11 h-11 bg-[#021F35] dark:bg-slate-800 text-white rounded-full flex items-center justify-center hover:bg-[#021F35]/90 active:scale-95 transition-all cursor-pointer shadow-sm"
                        >
                            <ListFilter class="h-5 w-5" />
                        </button>
                        <button 
                            @click="showSettingsModal = true"
                            class="w-11 h-11 bg-[#021F35] dark:bg-slate-800 text-white rounded-full flex items-center justify-center hover:bg-[#021F35]/90 active:scale-95 transition-all cursor-pointer shadow-sm"
                        >
                            <SlidersHorizontal class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Grid/Carousel Container -->
                <div v-if="filteredAudiobooks.length === 0" class="flex flex-col items-center justify-center bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-12 text-center">
                    <HelpCircle class="h-12 w-12 text-slate-400 mb-4 animate-bounce" />
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">No audiobooks match your filters</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm">Try loosening your search terms or clearing the favorites constraint to see more audiobooks.</p>
                    <Button variant="outline" @click="resetFilters" class="mt-5 rounded-xl font-bold">Clear All Filters</Button>
                </div>

                <div 
                    v-else
                    ref="audiobooksContainer"
                    class="flex overflow-x-auto gap-6 pb-4 scrollbar-hide snap-x snap-mandatory scroll-smooth"
                >
                    <!-- Audiobook Card -->
                    <Card 
                        v-for="book in filteredAudiobooks" 
                        :key="book.id"
                        class="w-[330px] md:w-[350px] shrink-0 border border-slate-200/60 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-[28px] overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 hover:translate-y-[-2px] snap-start"
                    >
                        <CardContent class="p-0">
                            <!-- Card cover with overlay & buttons -->
                            <div class="relative h-[220px] w-full bg-slate-100 overflow-hidden group">
                                <img 
                                    :src="book.image" 
                                    :alt="book.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                
                                <!-- Black opaque overlay -->
                                <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>

                                <!-- Central Interactive Play Button -->
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <button 
                                        @click="togglePlayAudiobook(book)"
                                        class="w-13 h-13 rounded-full bg-[#021F35]/85 text-white flex items-center justify-center hover:bg-[#021F35] shadow-lg cursor-pointer transform transition-all duration-200 hover:scale-110 active:scale-95"
                                        :title="playingTrack?.id === book.id && isPlaying ? 'Pause' : 'Play'"
                                    >
                                        <Pause v-if="playingTrack?.id === book.id && isPlaying" class="h-6 w-6 fill-white" />
                                        <Play v-else class="h-6 w-6 fill-white ml-1" />
                                    </button>
                                </div>

                                <!-- Heart Toggle Button (Top Right) -->
                                <div class="absolute top-4 right-4">
                                    <button 
                                        @click="toggleFavoriteAudiobook(book.id)"
                                        class="w-9 h-9 rounded-full bg-white dark:bg-slate-900 shadow-md flex items-center justify-center hover:scale-110 active:scale-90 transition-transform cursor-pointer"
                                        :class="book.favorite ? 'text-rose-500 dark:text-rose-400' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                                    >
                                        <Heart class="h-4.5 w-4.5" :fill="book.favorite ? 'currentColor' : 'none'" />
                                    </button>
                                </div>
                            </div>

                            <!-- Title, Author & Description -->
                            <div class="p-6 space-y-3">
                                <div class="space-y-1">
                                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100 leading-snug tracking-tight truncate" :title="book.title">
                                        {{ book.title }}
                                    </h3>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                        <span class="text-slate-400 dark:text-slate-500 font-medium text-xs uppercase tracking-wider">Author:</span>
                                        <span>{{ book.author }}</span>
                                    </p>
                                </div>

                                <p class="text-[13px] text-slate-500 dark:text-slate-400 leading-relaxed min-h-[58px] line-clamp-3">
                                    {{ book.description }}
                                </p>

                                <div class="pt-4 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between">
                                    <div class="text-[13px] font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                        <span class="text-slate-400 dark:text-slate-500 font-medium text-xs uppercase tracking-wider">Duration:</span>
                                        <span>{{ book.duration }}</span>
                                    </div>
                                    <Badge v-if="playingTrack?.id === book.id" class="bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-2 py-0.5 rounded-lg text-[10px] font-black uppercase">
                                        {{ isPlaying ? 'Playing' : 'Paused' }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- ARTICLES SECTION -->
            <div 
                v-if="activeCategory === 'all' || activeCategory === 'articles'" 
                class="space-y-4"
            >
                <!-- Section Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <BookOpen class="h-6 w-6 text-[#021F35] dark:text-blue-400" />
                        <h2 class="text-2xl font-extrabold tracking-tight text-[#021F35] dark:text-slate-50">Articles</h2>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Navigation chevrons (horizontal scrolling control) -->
                        <button 
                            @click="scroll(articlesContainer, 'left')"
                            class="w-10 h-10 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition-all text-slate-600 dark:text-slate-300"
                        >
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <button 
                            @click="scroll(articlesContainer, 'right')"
                            class="w-10 h-10 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition-all text-slate-600 dark:text-slate-300"
                        >
                            <ChevronRight class="h-5 w-5" />
                        </button>

                        <!-- Circular buttons as in Image -->
                        <button 
                            @click="showFiltersModal = true"
                            class="w-11 h-11 bg-[#021F35] dark:bg-slate-800 text-white rounded-full flex items-center justify-center hover:bg-[#021F35]/90 active:scale-95 transition-all cursor-pointer shadow-sm"
                        >
                            <ListFilter class="h-5 w-5" />
                        </button>
                        <button 
                            @click="showSettingsModal = true"
                            class="w-11 h-11 bg-[#021F35] dark:bg-slate-800 text-white rounded-full flex items-center justify-center hover:bg-[#021F35]/90 active:scale-95 transition-all cursor-pointer shadow-sm"
                        >
                            <SlidersHorizontal class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Grid/Carousel Container -->
                <div v-if="filteredArticles.length === 0" class="flex flex-col items-center justify-center bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-12 text-center">
                    <HelpCircle class="h-12 w-12 text-slate-400 mb-4 animate-bounce" />
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">No articles match your filters</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm">Try loosening your search terms or clearing the favorites constraint to see more articles.</p>
                    <Button variant="outline" @click="resetFilters" class="mt-5 rounded-xl font-bold">Clear All Filters</Button>
                </div>

                <div 
                    v-else
                    ref="articlesContainer"
                    class="flex overflow-x-auto gap-6 pb-4 scrollbar-hide snap-x snap-mandatory scroll-smooth"
                >
                    <!-- Article Card -->
                    <Card 
                        v-for="article in filteredArticles" 
                        :key="article.id"
                        class="w-[330px] md:w-[350px] shrink-0 border border-slate-200/60 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-[28px] overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 hover:translate-y-[-2px] snap-start"
                    >
                    <CardContent class="p-0">
                                <a :href="article.url" target="_blank">
                                <!-- Card cover with overlay & heart button -->
                                <div class="relative h-[220px] w-full bg-slate-100 overflow-hidden group">
                                    <img 
                                        :src="article.image" 
                                        :alt="article.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy"
                                    />
                                    
                                    <!-- Black opaque overlay -->
                                    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>

                                    <!-- Heart Toggle Button (Top Right) -->
                                    <div class="absolute top-4 right-4">
                                        <button 
                                            @click="toggleFavoriteArticle(article.id)"
                                            class="w-9 h-9 rounded-full bg-white dark:bg-slate-900 shadow-md flex items-center justify-center hover:scale-110 active:scale-90 transition-transform cursor-pointer"
                                            :class="article.favorite ? 'text-rose-500 dark:text-rose-400' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                                        >
                                            <Heart class="h-4.5 w-4.5" :fill="article.favorite ? 'currentColor' : 'none'" />
                                        </button>
                                    </div>
                                </div>

                                    <!-- Title, Tags & Reading Time -->
                                    <div class="p-6 space-y-4">
                                        <div class="space-y-1.5 min-h-[92px]">
                                            <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 leading-snug tracking-tight line-clamp-3" :title="article.title">
                                                "{{ article.title }}"
                                            </h3>
                                            <div class="flex flex-wrap gap-1.5 pt-2">
                                                <Badge 
                                                    v-for="tag in article.tags" 
                                                    :key="tag" 
                                                    variant="secondary" 
                                                    class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                                >
                                                    {{ tag }}
                                                </Badge>
                                            </div>
                                        </div>

                                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800/60 flex items-center gap-2 text-[13px] font-bold text-slate-700 dark:text-slate-300">
                                            <Clock class="h-4 w-4 text-indigo-500 shrink-0" />
                                            <span>⏳ Reading Time: {{ article.readingTime }}</span>
                                        </div>
                                    </div>
                            </a>
                            </CardContent>
                    </Card>
                </div>
            </div>

        </div>

        <!-- STICKY FLOATING AUDIO PLAYER (Slide-in) -->
        <transition 
            enter-active-class="transform transition duration-500 ease-out"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transform transition duration-400 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div 
                v-if="playingTrack" 
                class="fixed bottom-6 left-6 right-6 md:left-1/2 md:right-auto md:-translate-x-1/2 md:w-[680px] z-40 bg-[#021F35] text-white border border-slate-800/60 rounded-[32px] shadow-2xl p-4 md:p-5 flex items-center gap-4 transition-all duration-300"
            >
                <!-- Thumbnail -->
                <div class="h-14 w-14 rounded-2xl bg-slate-800 overflow-hidden shrink-0 shadow-md">
                    <img :src="playingTrack.image" :alt="playingTrack.title" class="h-full w-full object-cover" />
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0 mr-2">
                    <h4 class="text-sm font-extrabold text-white truncate leading-tight tracking-tight">{{ playingTrack.title }}</h4>
                    <p class="text-xs font-semibold text-slate-300 truncate mt-0.5 flex items-center gap-1.5">
                        <span class="text-slate-400 font-medium text-[10px] uppercase tracking-wider">By</span>
                        <span>{{ playingTrack.author }}</span>
                    </p>
                    
                    <!-- Progress Bar (Simulated) -->
                    <div class="flex items-center gap-3 mt-2.5">
                        <span class="text-[10px] font-extrabold text-slate-400 shrink-0 w-8 text-right">
                            {{ formatPlayerTime(currentTime) }}
                        </span>
                        
                        <input 
                            type="range" 
                            min="0" 
                            :max="playingTrack.durationMinutes * 60" 
                            :value="currentTime" 
                            @input="handleSeek"
                            class="flex-1 h-1 bg-slate-700 rounded-full appearance-none cursor-pointer focus:outline-none accent-indigo-500"
                        />

                        <span class="text-[10px] font-extrabold text-slate-400 shrink-0 w-8">
                            {{ playingTrack.duration }}
                        </span>
                    </div>
                </div>

                <!-- Player Controls -->
                <div class="flex items-center gap-3 shrink-0">
                    <!-- Playback Speed Control -->
                    <button 
                        @click="playbackSpeed = playbackSpeed === 1 ? 1.5 : playbackSpeed === 1.5 ? 2 : 1"
                        class="px-2.5 py-1.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-[10px] font-black uppercase tracking-wider select-none shrink-0"
                        title="Speed"
                    >
                        {{ playbackSpeed }}x
                    </button>

                    <!-- Play/Pause toggle -->
                    <button 
                        @click="isPlaying = !isPlaying; isPlaying ? startPlaybackTimer() : stopPlaybackTimer()"
                        class="w-11 h-11 rounded-full bg-indigo-600 hover:bg-indigo-500 text-white flex items-center justify-center transition-all cursor-pointer shadow-md hover:scale-105 active:scale-95"
                    >
                        <Pause v-if="isPlaying" class="h-5 w-5 fill-white" />
                        <Play v-else class="h-5 w-5 fill-white ml-0.5" />
                    </button>

                    <!-- Close/Dismiss player -->
                    <button 
                        @click="stopPlaybackTimer(); playingTrack = null; isPlaying = false"
                        class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-all cursor-pointer"
                        title="Close player"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>
            </div>
        </transition>

        <!-- FILTER & SORT DIALOG -->
        <Dialog :open="showFiltersModal" @update:open="showFiltersModal = $event">
            <DialogContent class="sm:max-w-[480px] rounded-[32px] border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 md:p-8 shadow-xl">
                <DialogHeader class="mb-5">
                    <DialogTitle class="text-2xl font-black text-slate-900 dark:text-slate-50 flex items-center gap-2">
                        <ListFilter class="h-6 w-6 text-indigo-500" />
                        <span>Filter & Sort Options</span>
                    </DialogTitle>
                    <DialogDescription class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Customize your development hub viewing preference and sort columns dynamically.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-6 py-2">
                    <!-- Category Select -->
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Category View</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                @click="activeCategory = 'all'"
                                :class="activeCategory === 'all' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-800'"
                                class="px-3 py-2.5 text-xs rounded-xl transition-all font-semibold"
                            >
                                All Items
                            </button>
                            <button 
                                @click="activeCategory = 'audiobooks'"
                                :class="activeCategory === 'audiobooks' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-800'"
                                class="px-3 py-2.5 text-xs rounded-xl transition-all font-semibold"
                            >
                                Audiobooks
                            </button>
                            <button 
                                @click="activeCategory = 'articles'"
                                :class="activeCategory === 'articles' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-800'"
                                class="px-3 py-2.5 text-xs rounded-xl transition-all font-semibold"
                            >
                                Articles
                            </button>
                        </div>
                    </div>

                    <!-- Sorting Preference -->
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Sort Ordering</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button 
                                @click="sortBy = 'none'"
                                :class="sortBy === 'none' ? 'bg-primary text-primary-foreground font-bold' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-800'"
                                class="px-3 py-2.5 text-xs rounded-xl transition-all font-semibold"
                            >
                                Default Order
                            </button>
                            <button 
                                @click="sortBy = 'title'"
                                :class="sortBy === 'title' ? 'bg-primary text-primary-foreground font-bold' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-800'"
                                class="px-3 py-2.5 text-xs rounded-xl transition-all font-semibold"
                            >
                                Sort by Title
                            </button>
                            <button 
                                @click="sortBy = 'duration_asc'"
                                :class="sortBy === 'duration_asc' ? 'bg-primary text-primary-foreground font-bold' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-800'"
                                class="px-3 py-2.5 text-xs rounded-xl transition-all font-semibold col-span-1"
                            >
                                Shortest First
                            </button>
                            <button 
                                @click="sortBy = 'duration_desc'"
                                :class="sortBy === 'duration_desc' ? 'bg-primary text-primary-foreground font-bold' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-800'"
                                class="px-3 py-2.5 text-xs rounded-xl transition-all font-semibold col-span-1"
                            >
                                Longest First
                            </button>
                        </div>
                    </div>

                    <!-- Favorites Constraint toggle -->
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/50 dark:border-slate-800">
                        <div class="space-y-0.5">
                            <span class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Favorites Only</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Display only books/articles you have favorited.</p>
                        </div>
                        <input 
                            type="checkbox" 
                            v-model="filterFavoriteOnly"
                            class="h-5 w-5 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                        />
                    </div>
                </div>

                <DialogFooter class="mt-6 border-t border-slate-100 dark:border-slate-800 pt-4 flex gap-2">
                    <Button variant="outline" @click="resetFilters" class="rounded-xl font-bold">
                        Reset Filters
                    </Button>
                    <Button @click="showFiltersModal = false" class="rounded-xl font-bold bg-[#021F35] text-white hover:bg-[#021F35]/90">
                        Apply Changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- PREFERENCES/SETTINGS DIALOG -->
        <Dialog :open="showSettingsModal" @update:open="showSettingsModal = $event">
            <DialogContent class="sm:max-w-[460px] rounded-[32px] border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 md:p-8 shadow-xl">
                <DialogHeader class="mb-5">
                    <DialogTitle class="text-2xl font-black text-slate-900 dark:text-slate-50 flex items-center gap-2">
                        <SlidersHorizontal class="h-6 w-6 text-amber-500 animate-pulse" />
                        <span>Hub Settings</span>
                    </DialogTitle>
                    <DialogDescription class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Configure additional preferences for media players and learning hubs.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-5 py-2">
                    <div class="space-y-1.5">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Learning Speed</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-normal mb-2">Set the default simulation playback speed for your audiobook media player.</p>
                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                v-for="speed in [1, 1.5, 2]" 
                                :key="speed"
                                @click="playbackSpeed = speed"
                                :class="playbackSpeed === speed ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-800'"
                                class="px-3 py-2.5 text-xs rounded-xl transition-all"
                            >
                                {{ speed }}x Speed
                            </button>
                        </div>
                    </div>

                    <div class="p-4 bg-amber-50/50 dark:bg-slate-950/40 rounded-2xl border border-amber-100 dark:border-amber-950/40 flex gap-3">
                        <Sparkles class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-amber-900 dark:text-amber-400">Pro Tip</span>
                            <p class="text-xs text-amber-700/80 dark:text-amber-500 leading-normal">You can listen and scroll simultaneously! Play an audiobook, search, apply filters, and look up articles without pausing your session.</p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="mt-6 border-t border-slate-100 dark:border-slate-800 pt-4">
                    <Button @click="showSettingsModal = false" class="w-full rounded-xl font-bold bg-[#021F35] text-white hover:bg-[#021F35]/90">
                        Dismiss
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
/* Custom styled slider */
input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #6366f1;
    cursor: pointer;
    box-shadow: 0 0 4px rgba(0,0,0,0.3);
    transition: transform 0.1s;
}
input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}
input[type="range"]::-moz-range-thumb {
    width: 12px;
    height: 12px;
    border: none;
    border-radius: 50%;
    background: #6366f1;
    cursor: pointer;
    box-shadow: 0 0 4px rgba(0,0,0,0.3);
    transition: transform 0.1s;
}
input[type="range"]::-moz-range-thumb:hover {
    transform: scale(1.2);
}

/* Hide scrollbar utility */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
