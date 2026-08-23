<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const mobileMenuOpen = ref(false);
</script>

<template>
    <Head title="Welcome" />

    <div
        class="relative flex min-h-screen flex-col overflow-hidden bg-[#021526] font-sans antialiased selection:bg-white/10 selection:text-white"
    >
        <header
            :class="[
                'fixed top-0 left-0 z-50 flex w-full items-start justify-between transition-all duration-300 lg:left-19',
                mobileMenuOpen ? 'bg-white backdrop-blur-sm' : '',
            ]"
            :style="
                mobileMenuOpen
                    ? {
                          backgroundImage:
                              'linear-gradient(to right, rgb(255, 255, 255), rgba(255, 255, 255, 0))',
                      }
                    : {}
            "
        >
            <!-- Left Nav Box -->
            <!--           <nav
                class="hidden rounded-br-3xl rounded-bl-3xl bg-white px-8 py-5 lg:flex xl:px-12 xl:py-6"
            >
                <ul
                    class="flex items-center gap-8 text-[15px] font-extrabold text-gray-700"
                >
                    <li><a href="#" class="hover:text-black">Features</a></li>
                    <li><a href="#" class="hover:text-black">Services</a></li>
                    <li><a href="#" class="hover:text-black">Prices</a></li>
                    <li><a href="#" class="hover:text-black">Blog</a></li>
                    <li><a href="#" class="hover:text-black">About us</a></li>
                </ul>
            </nav>
 -->
            <!-- Mobile Menu Button -->

            <!--      <div class="rounded-br-2xl bg-white p-5 lg:hidden">
                <button
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="flex items-center justify-center text-black"
                >
                    <svg
                        v-if="!mobileMenuOpen"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <line x1="4" x2="20" y1="12" y2="12" />
                        <line x1="4" x2="20" y1="6" y2="6" />
                        <line x1="4" x2="20" y1="18" y2="18" />
                    </svg>
                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <line x1="18" x2="6" y1="6" y2="18" />
                        <line x1="6" x2="18" y1="6" y2="18" />
                    </svg>
                </button>
            </div>
 -->
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

        <!-- Mobile Menu -->
        <!--         <transition
            enter-active-class="transition duration-200"
            leave-active-class="transition duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileMenuOpen"
                class="fixed inset-0 top-16 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
            ></div>
        </transition> -->

        <!--         <transition
            enter-active-class="transition duration-200"
            leave-active-class="transition duration-200"
            enter-from-class="-translate-x-full"
            leave-to-class="-translate-x-full"
        >
            <nav
                v-if="mobileMenuOpen"
                class="fixed top-16 left-0 z-40 w-full bg-white lg:hidden"
            >
                <ul
                    class="flex flex-col gap-4 p-6 text-[15px] font-extrabold text-gray-700"
                >
                    <li>
                        <a
                            href="#"
                            @click="mobileMenuOpen = false"
                            class="block py-2 hover:text-black"
                            >Features</a
                        >
                    </li>
                    <li>
                        <a
                            href="#"
                            @click="mobileMenuOpen = false"
                            class="block py-2 hover:text-black"
                            >Services</a
                        >
                    </li>
                    <li>
                        <a
                            href="#"
                            @click="mobileMenuOpen = false"
                            class="block py-2 hover:text-black"
                            >Prices</a
                        >
                    </li>
                    <li>
                        <a
                            href="#"
                            @click="mobileMenuOpen = false"
                            class="block py-2 hover:text-black"
                            >Blog</a
                        >
                    </li>
                    <li>
                        <a
                            href="#"
                            @click="mobileMenuOpen = false"
                            class="block py-2 hover:text-black"
                            >About us</a
                        >
                    </li>
                </ul>
            </nav>
        </transition> -->

        <!-- Main Content -->
        <main class="flex flex-1 flex-col pt-16 lg:flex-row lg:pt-0">
            <!-- Left Column -->
            <section
                class="relative flex w-full flex-col justify-center px-6 pt-16 pb-8 lg:w-[55%] lg:px-12 lg:pt-0 lg:pb-0 [@media(max-width:1179px)]:origin-left [@media(max-width:1179px)]:scale-90"
            >
                <div class="max-w-xl">
                    <!-- Logo -->
                    <div class="mb-4 flex items-center gap-4">
                        <div
                            class="relative flex h-20 items-center justify-center [@media(max-width:1179px)]:h-16"
                        >
                            <img
                                src="/flow_bg_transparent.png"
                                alt="Flow Icon"
                                class="h-full w-full object-cover"
                            />
                        </div>
                    </div>

                    <!-- Hero Text -->
                    <div class="mb-8 [@media(max-width:1179px)]:mb-6">
                        <h1
                            class="mb-2 flex flex-row items-center gap-12 text-4xl leading-tight font-bold tracking-tight text-white lg:text-6xl [@media(max-width:1179px)]:text-5xl"
                        >
                            Flow
                            <span
                                class="block text-[0.4em] font-medium opacity-90 lg:inline"
                                >Revolutionizing Hiring &
                                <br class="hidden lg:block" />Career Growth with
                                AI</span
                            >
                        </h1>
                        <div class="h-px w-full bg-white/20"></div>
                        <p
                            class="max-w-md pt-2 text-lg leading-relaxed text-gray-400 [@media(max-width:1179px)]:text-base"
                        >
                            AI-powered job matching and workforce automation for
                            professionals and businesses.
                        </p>
                    </div>

                    <!-- Split Actions -->
                    <div
                        class="grid grid-cols-1 gap-8 sm:grid-cols-2 [@media(max-width:1179px)]:gap-4"
                    >
                        <!-- JobFlow -->
                        <div
                            class="flex flex-col gap-4 [@media(max-width:1179px)]:gap-2"
                        >
                            <div class="flex flex-col gap-1">
                                <h2
                                    class="text-xl font-bold text-white [@media(max-width:1179px)]:text-lg"
                                >
                                    JobFlow
                                </h2>
                                <div class="h-px w-full bg-white/20"></div>
                            </div>
                            <a
                                href="/jobflow"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex h-10 items-center justify-center rounded-full bg-white px-6 text-xs font-bold tracking-wide text-black uppercase transition-transform hover:scale-103 active:scale-95 [@media(max-width:1179px)]:h-10 [@media(max-width:1179px)]:px-6 [@media(max-width:1179px)]:text-xs"
                            >
                                Find your perfect job
                            </a>
                        </div>

                        <!-- HRFlow -->
                        <div
                            class="flex flex-col gap-4 [@media(max-width:1179px)]:gap-2"
                        >
                            <div class="flex flex-col gap-1">
                                <h2
                                    class="text-xl font-bold text-white [@media(max-width:1179px)]:text-lg"
                                >
                                    HRFlow
                                </h2>
                                <div class="h-px w-full bg-white/20"></div>
                            </div>
                            <a
                                href="/hrflow"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex h-10 items-center justify-center rounded-full bg-white px-6 text-xs font-bold tracking-wide text-black uppercase transition-transform hover:scale-103 active:scale-95 [@media(max-width:1179px)]:h-10 [@media(max-width:1179px)]:px-6 [@media(max-width:1179px)]:text-xs"
                            >
                                Manage your best team
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Right Column (Image) -->
            <section class="relative w-full overflow-hidden lg:w-[45%]">
                <img
                    src="/robot_interview_scene_edited.png"
                    alt="AI Interview"
                    class="h-full w-full object-cover contrast-[1.1] grayscale-[0.2]"
                />
                <!-- Subtle Gradient Overlay -->
                <div
                    class="absolute inset-0 hidden bg-gradient-to-r from-[#021526] via-transparent to-transparent lg:block"
                ></div>
                <div
                    class="absolute inset-0 block bg-gradient-to-t from-[#021526] via-transparent to-transparent lg:hidden"
                ></div>
            </section>
        </main>

        <!-- Footer -->
        <!--         <footer class="absolute bottom-0 z-50 w-full p-6">
            <div
                class="flex flex-col items-center gap-4 text-[12px] font-bold tracking-[0.1em] text-gray-500 uppercase sm:flex-row sm:gap-18 sm:text-[14px]"
            >
                <div class="flex items-center gap-6">
                    <span>JobFlow © 2026. All rights reserved</span>
                </div>
                <div class="flex items-center gap-6 sm:gap-18">
                    <a href="#" class="transition-colors hover:text-white"
                        >Privacy Policy</a
                    >
                    <a href="#" class="transition-colors hover:text-white"
                        >Terms of Use</a
                    >
                </div>
            </div>
        </footer> -->
    </div>
</template>
