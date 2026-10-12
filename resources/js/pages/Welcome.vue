
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PublicNav from '@/components/PublicNav.vue';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('welcome.title')" />

    <PublicNav />

    <div class="relative min-h-screen overflow-hidden bg-[#080b10] text-[#f0f6fc]" style="background-image: radial-gradient( ellipse 900px 600px at 50% 0%, rgba(31, 111, 235, 0.22), transparent 70%);">
        
        <main class="relative mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8">

            <!-- Hero -->
            <header class="mb-14 flex flex-col items-center text-center">
                <div class="relative mb-6">
                    <div class="absolute inset-0 scale-[1.8] rounded-full bg-[#1f6feb]/20 blur-2xl"></div>

                    <div class="relative flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] shadow-2xl shadow-[#1f6feb]/20 backdrop-blur-xl">
                        <img src="/favicon.png" alt="GitHub Repository Sync" class="h-11 w-11 rounded-xl"/>
                    </div>
                </div>

                <div class="mb-3 flex items-center gap-2 rounded-full border border-[#30363d] bg-[#161b22]/70 px-3 py-1 text-xs font-medium text-[#8b949e] backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#3fb950] shadow-[0_0_8px_#3fb950]"></span>
                    {{ t('welcome.badge') }}
                </div>

                <h1 class="bg-gradient-to-b from-white to-[#8b949e] bg-clip-text text-4xl font-bold tracking-tight text-transparent sm:text-5xl">
                    {{ t('welcome.title') }}
                </h1>

                <p class="mt-4 max-w-xl text-base leading-7 text-[#8b949e]">
                    {{ t('welcome.description') }}
                </p>
            </header>

            <!-- Navigation cards -->
            <section class="mx-auto grid max-w-4xl gap-6 md:grid-cols-2">
                <Link
                    href="/sync-targets"
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-[#11161d]/90 p-7 shadow-2xl shadow-black/20 backdrop-blur-xl transition duration-200 hover:-translate-y-1 hover:border-[#58a6ff]/40 hover:shadow-[#1f6feb]/10"
                >
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl border border-[#58a6ff]/20 bg-[#58a6ff]/10 text-2xl text-[#58a6ff]">
                        ↻
                    </div>

                    <h2 class="text-lg font-semibold tracking-tight text-[#f0f6fc]">
                        {{ t('welcome.targets.title') }}
                    </h2>

                    <p class="mt-3 flex-1 text-sm leading-6 text-[#8b949e]">
                        {{ t('welcome.targets.description') }}
                    </p>

                    <div class="mt-8 flex items-center gap-2 text-sm font-semibold text-[#58a6ff]">
                        {{ t('welcome.targets.action') }}
                        <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
                    </div>
                </Link>

                <Link
                    href="/failed-jobs"
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-[#11161d]/90 p-7 shadow-2xl shadow-black/20 backdrop-blur-xl transition duration-200 hover:-translate-y-1 hover:border-[#58a6ff]/40 hover:shadow-[#1f6feb]/10"
                >
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl border border-[#58a6ff]/20 bg-[#58a6ff]/10 text-2xl text-[#58a6ff]">
                        ⚠
                    </div>

                    <h2 class="text-lg font-semibold tracking-tight text-[#f0f6fc]">
                        {{ t('welcome.failedJobs.title') }}
                    </h2>

                    <p class="mt-3 flex-1 text-sm leading-6 text-[#8b949e]">
                        {{ t('welcome.failedJobs.description') }}
                    </p>

                    <div class="mt-8 flex items-center gap-2 text-sm font-semibold text-[#58a6ff]">
                        {{ t('welcome.failedJobs.action') }}
                        <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
                    </div>
                </Link>
            </section>

            <footer class="mt-16 text-center text-xs text-[#6e7681]">
                <span v-html="t('welcome.footer')"></span>
            </footer>
        </main>
    </div>
</template>
