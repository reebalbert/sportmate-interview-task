<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PublicNav from '@/components/PublicNav.vue';

const { t, locale } = useI18n();

interface FailedJob {
    id: number;
    uuid: string;
    connection: string;
    queue: string;
    payload: string;
    exception: string;
    failed_at: string;
}

defineProps<{
    failedJobs: FailedJob[];
}>();

const formatDate = (value: string) => {
    return new Intl.DateTimeFormat(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const retryingJobId = ref<number | null>(null);

const retryJob = (job: FailedJob) => {
    retryingJobId.value = job.id;

    router.post(
        `/failed-jobs/${job.id}/retry`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                retryingJobId.value = null;
            },
        },
    );
};
</script>

<template>
    <Head :title="t('failedJobs.title')" />

    <PublicNav />

    <div
        class="relative min-h-screen overflow-hidden bg-[#080b10] text-[#f0f6fc]"
        style="
            background-image: radial-gradient(
                ellipse 900px 600px at 50% 0%,
                rgba(31, 111, 235, 0.22),
                transparent 70%
            );
        "
    >
        <main class="relative mx-auto w-full max-w-7xl px-6 py-12">
            <h1 class="text-4xl font-bold">
                {{ t('failedJobs.title') }}
            </h1>

            <p class="mt-3 text-[#8b949e]">
                {{ t('failedJobs.description') }}
            </p>

            <div
                v-if="failedJobs.length === 0"
                class="mt-8 rounded-xl border border-[#30363d] bg-[#161b22] p-8 text-[#8b949e]"
            >
                {{ t('failedJobs.empty') }}
            </div>

            <div v-else class="mt-8 space-y-4">
                <div
                    v-for="job in failedJobs"
                    :key="job.id"
                    class="rounded-xl border border-[#30363d] bg-[#161b22] p-6"
                >
                    <div class="flex items-center justify-between gap-6">
                        <div class="min-w-0">
                            <div class="truncate font-semibold">
                                {{ job.uuid }}
                            </div>

                            <div class="mt-2 text-sm text-[#8b949e]">
                                {{ t('failedJobs.fields.connection') }}:
                                {{ job.connection }} ·
                                {{ t('failedJobs.fields.queue') }}:
                                {{ job.queue }} ·
                                {{ t('failedJobs.fields.failedAt') }}:
                                {{ formatDate(job.failed_at) }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-lg bg-[#238636] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#2ea043] disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="retryingJobId === job.id"
                            @click="retryJob(job)"
                        >
                            {{
                                retryingJobId === job.id
                                    ? t('failedJobs.actions.retrying')
                                    : t('failedJobs.actions.retry')
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
