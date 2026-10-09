<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();

const isActive = (path: string) => page.url.startsWith(path);
</script>

<template>
    <nav class="border-b border-white/[0.07] bg-[#080b10]/80 backdrop-blur-xl">
        <div
            class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8"
        >
            <Link href="/sync-targets" class="flex items-center gap-2">
                <img src="/favicon.png" alt="" class="h-8 w-8 rounded-lg" />
                <span class="text-sm font-semibold text-[#f0f6fc]">{{
                    t('navigation.appName')
                }}</span>
            </Link>

            <div class="flex items-center gap-1">
                <Link
                    href="/sync-targets"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition"
                    :class="
                        isActive('/sync-targets')
                            ? 'bg-[#21262d] text-white'
                            : 'text-[#8b949e] hover:bg-[#161b22] hover:text-white'
                    "
                >
                    {{ t('navigation.syncTargets') }}
                </Link>

                <Link
                    href="/failed-jobs"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition"
                    :class="
                        isActive('/failed-jobs')
                            ? 'bg-[#21262d] text-white'
                            : 'text-[#8b949e] hover:bg-[#161b22] hover:text-white'
                    "
                >
                    {{ t('navigation.failedJobs') }}
                </Link>
            </div>
        </div>
    </nav>
</template>
