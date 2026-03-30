<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import { ChevronDown } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome" />

    <div class="relative flex min-h-screen flex-col overflow-hidden bg-[#021526] font-sans antialiased selection:bg-white/10 selection:text-white">
        <!-- Header -->
        <header class="absolute top-0 left-19 z-50 flex w-full items-start justify-between">
            <!-- Left Nav Box -->
            <nav class="hidden rounded-bl-3xl rounded-br-3xl bg-white px-8 py-5 lg:flex xl:px-12 xl:py-6">
                <ul class="flex items-center gap-8 text-[15px] font-extrabold text-gray-700">
                    <li><a href="#" class="hover:text-black">Features</a></li>
                    <li><a href="#" class="hover:text-black">Services</a></li>
                    <li><a href="#" class="hover:text-black">Prices</a></li>
                    <li><a href="#" class="hover:text-black">Blog</a></li>
                    <li><a href="#" class="hover:text-black">About us</a></li>
                </ul>
            </nav>

            <!-- Mobile Menu Button -->
            <div class="rounded-br-2xl bg-white p-5 lg:hidden">
                <button class="flex items-center justify-center text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" x2="20" y1="12" y2="12"/>
                        <line x1="4" x2="20" y1="6" y2="6"/>
                        <line x1="4" x2="20" y1="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Right Language Switcher Pill -->
<!--             <div class="p-5 lg:p-6 xl:p-8">
                <div class="flex cursor-pointer items-center gap-3 rounded-lg bg-[#010c16] px-4 py-2.5 text-sm font-bold text-white shadow-lg ring-1 ring-white/10 hover:bg-[#010c16]/80">
                    <span class="flex h-4 w-6 items-center justify-center overflow-hidden rounded-[2px] bg-blue-900 text-[11px]">
                        🇺🇸
                    </span>
                    <span>English (en)</span>
                    <ChevronDown class="h-4 w-4 opacity-70" />
                </div>
            </div> -->
        </header>

        <!-- Main Content -->
        <main class="flex flex-1 flex-col lg:flex-row">
            <!-- Left Column -->
            <section class="relative flex w-full flex-col justify-center px-6 pt-32 pb-16 lg:w-[55%] lg:px-20 lg:pt-0 lg:pb-0">
                <div class="max-w-xl">
                    <!-- Logo -->
                    <div class="mb-4 flex items-center gap-4">
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-xl bg-gradient-to-br from-white/10 to-transparent p-3 ring-1 ring-white/20">
                            <svg viewBox="0 0 24 24" class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
                                    fill="none"
                                    stroke="#fff"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold tracking-tight text-white">Flow Icon</span>
                    </div>

                    <!-- Hero Text -->
                    <div class="mb-12">
                        <h1 class="mb-4 text-5xl font-bold leading-tight tracking-tight text-white lg:text-7xl flex flex-row gap-12 items-center">
                            Flow 
                            <span class="text-[0.4em] font-medium opacity-90 block lg:inline">Revolutionizing Hiring & <br class="hidden lg:block">Career Growth with AI</span>
                        </h1>
                        <div class="h-px w-full bg-white/20"></div>
                        <p class="max-w-md text-lg leading-relaxed text-gray-400 pt-4">
                            AI-powered job matching and workforce automation for professionals and businesses.
                        </p>
                    </div>

                    <!-- Split Actions -->
                    <div class="grid grid-cols-1 gap-12 sm:grid-cols-2">
                        <!-- JobFlow -->
                        <div class="flex flex-col gap-6">
                            <div class="flex flex-col gap-2">
                                <h2 class="text-2xl font-bold text-white">JobFlow</h2>
                                <div class="h-px w-full bg-white/20"></div>
                            </div>
                            <Link :href="register()" class="inline-flex h-14 items-center justify-center rounded-full bg-white px-8 text-sm font-bold tracking-wide text-black uppercase transition-transform hover:scale-103 active:scale-95">
                                Find your perfect job
                            </Link>
                        </div>

                        <!-- HRFlow -->
                        <div class="flex flex-col gap-6">
                            <div class="flex flex-col gap-2">
                                <h2 class="text-2xl font-bold text-white">HRFlow</h2>
                                <div class="h-px w-full bg-white/20"></div>
                            </div>
                            <Link :href="register()" class="inline-flex h-14 items-center justify-center rounded-full bg-white px-8 text-sm font-bold tracking-wide text-black uppercase transition-transform hover:scale-103 active:scale-95">
                                Manage your best team
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Right Column (Image) -->
            <section class="relative w-full overflow-hidden lg:w-[45%]">
                <img 
                    src="https://images.unsplash.com/photo-1485827404703-89b55fcc595e?q=80&w=2070&auto=format&fit=crop" 
                    alt="AI Interview" 
                    class="h-full w-full object-cover grayscale-[0.2] contrast-[1.1]"
                />
                <!-- Subtle Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-[#021526] via-transparent to-transparent lg:block hidden"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#021526] via-transparent to-transparent lg:hidden block"></div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="absolute bottom-0 z-50 w-full p-6">
            <div class="flex flex-col items-center gap-18 text-[14px] font-bold tracking-[0.1em] text-gray-500 uppercase sm:flex-row">
                <div class="flex items-center gap-6">
                    <span>JobFlow © 2026. All rights reserved</span>
                </div>
                <div class="flex items-center gap-18">
                    <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms of Use</a>
                </div>
            </div>
        </footer>
    </div>
</template>
