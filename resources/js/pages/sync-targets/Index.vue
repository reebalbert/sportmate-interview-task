<script setup lang="ts">
import { Head, router, useForm, usePoll } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import PublicNav from '@/components/PublicNav.vue';

const { t, locale } = useI18n();

interface Repository {
    id: number;
    github_id: number;
    name: string;
    full_name: string;
    description: string | null;
    html_url: string;
    language: string | null;
    stargazers_count: number;
    open_issues_count: number;
    archived: boolean;
    github_updated_at: string | null;
}

interface SyncTarget {
    id: number;
    name: string;
    type: 'user' | 'organization';
    status: 'never_synced' | 'syncing' | 'synced' | 'failed';
    last_synced_at: string | null;
    last_error: string | null;
    repositories_count: number;
    total_stars: number | null;
    total_issues: number | null;
    repositories: Repository[];
}

const props = defineProps<{
    targets: SyncTarget[];
    filters: {
        search: string;
        target_id: number;
    };
}>();

usePoll(2000, { only: ['targets'] });

const filters = ref<
    Record<number, { search: string; language: string; page: number }>
>({});

const getFilter = (targetId: number) => {
    if (!filters.value[targetId]) {
        filters.value[targetId] = {
            search: props.filters.target_id === targetId ? props.filters.search : '',
            language: '',
            page: 1,
        };
    }

    return filters.value[targetId];
};

let searchTimeout: ReturnType<typeof setTimeout>;

const searchRepositories = (targetId: number) => {
    getFilter(targetId).page = 1;

    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get('/sync-targets', {
            search: getFilter(targetId).search,
            target_id: targetId,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['targets', 'filters'],
        });
    }, 400);
};

const form = useForm({
    name: '',
    type: 'user',
});

const formatDate = (value: string) => {
    return new Intl.DateTimeFormat(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const submit = () => {
    form.post('/sync-targets', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const sync = (target: SyncTarget) => {
    router.post(`/sync-targets/${target.id}/sync`, {}, {
        preserveScroll: true,
    });
};

const languages = (target: SyncTarget) => {
    const values = target.repositories.map((repository) => repository.language);

    return [
        ...new Set(values.filter((value): value is string => value !== null)),
    ].sort();
};

const filteredRepositories = (target: SyncTarget) => {
    const filter = getFilter(target.id);

    return target.repositories.filter((repository) => {
        return !filter.language || repository.language === filter.language;
    });
};

const perPage = 20;

const paginatedRepositories = (target: SyncTarget) => {
    const repositories = filteredRepositories(target);
    const start = (getFilter(target.id).page - 1) * perPage;

    return repositories.slice(start, start + perPage);
};

const totalPages = (target: SyncTarget) => {
    return Math.ceil(filteredRepositories(target).length / perPage);
};

const totalStars = (target: SyncTarget) => {
    return target.repositories.reduce(
        (total, repository) => total + repository.stargazers_count,
        0,
    );
};

const totalIssues = (target: SyncTarget) => {
    return target.repositories.reduce(
        (total, repository) => total + repository.open_issues_count,
        0,
    );
};

const formatNumber = (value: number) => {
    return new Intl.NumberFormat('en', {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Synchronization Targets',
                href: '/sync-targets',
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('syncTargets.title')" />

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
        <main
            class="relative mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8"
        >
            <!-- Hero -->
            <header class="mb-12 flex flex-col items-center text-center">
                <div class="relative mb-6">
                    <div
                        class="absolute inset-0 scale-[1.8] rounded-full bg-[#1f6feb]/20 blur-2xl"
                    ></div>

                    <div
                        class="relative flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] shadow-2xl shadow-[#1f6feb]/20 backdrop-blur-xl"
                    >
                        <img
                            src="/favicon.png"
                            alt=""
                            class="h-11 w-11 rounded-xl"
                        />
                    </div>
                </div>

                <div
                    class="mb-3 flex items-center gap-2 rounded-full border border-[#30363d] bg-[#161b22]/70 px-3 py-1 text-xs font-medium text-[#8b949e] backdrop-blur"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-[#3fb950] shadow-[0_0_8px_#3fb950]"
                    ></span>
                    GitHub API
                </div>

                <h1
                    class="bg-gradient-to-b from-white to-[#8b949e] bg-clip-text text-4xl font-bold tracking-tight text-transparent sm:text-5xl"
                >
                    {{ t('syncTargets.title') }}
                </h1>

                <p class="mt-4 max-w-xl text-base leading-7 text-[#8b949e]">
                    {{ t('syncTargets.description') }}
                </p>
            </header>

            <!-- Add target -->
            <section class="relative mx-auto mb-8 max-w-5xl">
                <div
                    class="absolute -inset-px rounded-2xl bg-gradient-to-r from-[#1f6feb]/30 via-[#58a6ff]/10 to-[#1f6feb]/30 blur-sm"
                ></div>

                <div
                    class="relative overflow-hidden rounded-2xl border border-white/10 bg-[#11161d]/90 shadow-2xl shadow-black/30 backdrop-blur-xl"
                >
                    <div
                        class="flex items-center justify-between border-b border-white/[0.07] px-6 py-4"
                    >
                        <div>
                            <h2 class="text-sm font-semibold text-[#f0f6fc]">
                                {{ t('syncTargets.actions.add') }}
                            </h2>

                            <p class="mt-0.5 text-xs text-[#6e7681]">
                                github.com/<span class="text-[#58a6ff]">{{
                                    form.name || 'username'
                                }}</span>
                            </p>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-[#f85149]/80"
                            ></span>
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-[#d29922]/80"
                            ></span>
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-[#3fb950]/80"
                            ></span>
                        </div>
                    </div>

                    <form class="p-6" @submit.prevent="submit">
                        <div
                            class="grid items-start gap-4 md:grid-cols-[1fr_200px_auto]"
                        >
                            <div>
                                <label
                                    for="name"
                                    class="mb-2 block text-xs font-medium tracking-wider text-[#8b949e] uppercase"
                                >
                                    {{ t('syncTargets.fields.name') }}
                                </label>

                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    :placeholder="
                                        t('syncTargets.fields.namePlaceholder')
                                    "
                                    class="w-full rounded-lg border border-[#30363d] bg-[#080b10]/80 px-4 py-3 text-sm text-white transition duration-200 outline-none placeholder:text-[#484f58] hover:border-[#484f58] focus:border-[#58a6ff] focus:ring-2 focus:ring-[#1f6feb]/20"
                                />

                                <p
                                    v-if="form.errors.name"
                                    class="mt-2 text-xs text-[#f85149]"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="type"
                                    class="mb-2 block text-xs font-medium tracking-wider text-[#8b949e] uppercase"
                                >
                                    {{ t('syncTargets.fields.type') }}
                                </label>

                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="w-full rounded-lg border border-[#30363d] bg-[#080b10]/80 px-4 py-3 text-sm text-white transition duration-200 outline-none hover:border-[#484f58] focus:border-[#58a6ff] focus:ring-2 focus:ring-[#1f6feb]/20"
                                >
                                    <option value="user">
                                        {{ t('syncTargets.types.user') }}
                                    </option>
                                    <option value="organization">
                                        {{
                                            t('syncTargets.types.organization')
                                        }}
                                    </option>
                                </select>

                                <p
                                    v-if="form.errors.type"
                                    class="mt-2 text-xs text-[#f85149]"
                                >
                                    {{ form.errors.type }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="group relative mt-[26px] overflow-hidden rounded-lg bg-[#238636] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#238636]/10 transition duration-200 hover:-translate-y-0.5 hover:bg-[#2ea043] hover:shadow-[#238636]/20 disabled:pointer-events-none disabled:opacity-50"
                            >
                                <span class="relative">
                                    {{
                                        form.processing
                                            ? t('syncTargets.actions.adding')
                                            : t('syncTargets.actions.add')
                                    }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Empty state -->
            <section
                v-if="targets.length === 0"
                class="rounded-xl border border-dashed border-[#30363d] bg-[#161b22] px-6 py-16 text-center text-sm text-[#8b949e]"
            >
                {{ t('syncTargets.empty') }}
            </section>

            <!-- Targets -->
            <section
                v-for="target in targets"
                v-else
                :key="target.id"
                class="mt-5 overflow-hidden rounded-xl border border-[#30363d] bg-[#161b22]"
            >
                <!-- Target header -->
                <div class="border-b border-white/[0.07]">
                    <div
                        class="flex flex-col gap-5 px-6 py-6 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#58a6ff]/20 bg-[#58a6ff]/10 text-lg font-bold text-[#58a6ff] shadow-[0_0_25px_rgba(88,166,255,0.08)]"
                            >
                                {{ target.name.charAt(0).toUpperCase() }}
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2
                                        class="text-lg font-semibold tracking-tight text-white"
                                    >
                                        {{ target.name }}
                                    </h2>

                                    <span
                                        class="rounded-full border border-[#30363d] bg-[#21262d] px-2.5 py-0.5 text-[11px] font-medium text-[#8b949e]"
                                    >
                                        {{
                                            t(
                                                `syncTargets.types.${target.type}`,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="mt-1.5 flex items-center gap-2 text-xs text-[#8b949e]"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        :class="{
                                            'bg-[#3fb950] shadow-[0_0_8px_#3fb950]':
                                                target.status === 'synced',
                                            'animate-pulse bg-[#58a6ff] shadow-[0_0_8px_#58a6ff]':
                                                target.status === 'syncing',
                                            'bg-[#f85149] shadow-[0_0_8px_#f85149]':
                                                target.status === 'failed',
                                            'bg-[#6e7681]':
                                                target.status ===
                                                'never_synced',
                                        }"
                                    />

                                    <span>{{
                                        t(
                                            `syncTargets.statuses.${target.status}`,
                                        )
                                    }}</span>

                                    <template v-if="target.last_synced_at">
                                        <span class="text-[#484f58]">•</span>
                                        <span>{{
                                            formatDate(target.last_synced_at)
                                        }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="group flex items-center justify-center gap-2 rounded-lg border border-[#30363d] bg-[#21262d] px-4 py-2.5 text-sm font-medium text-[#f0f6fc] transition duration-200 hover:-translate-y-0.5 hover:border-[#58a6ff]/50 hover:bg-[#292e36]"
                            :disabled="target.status === 'syncing'"
                            @click="sync(target)"
                        >
                            <span
                                class="text-lg leading-none text-[#58a6ff] transition-transform duration-500 group-hover:rotate-180"
                                >↻</span
                            >
                            {{
                                target.status === 'syncing'
                                    ? t('syncTargets.actions.syncing')
                                    : t('syncTargets.actions.sync')
                            }}
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-3 border-t border-white/[0.05] bg-black/10"
                    >
                        <div class="px-6 py-5">
                            <div
                                class="text-2xl font-semibold tracking-tight text-white"
                            >
                                {{ target.repositories_count }}
                            </div>
                            <div
                                class="mt-1 text-[10px] font-semibold tracking-[0.16em] text-[#6e7681] uppercase"
                            >
                                {{ t('syncTargets.stats.repositories') }}
                            </div>
                        </div>

                        <div
                            class="border-l border-white/[0.05] px-6 py-5"
                        >
                            <div
                                class="text-2xl font-semibold tracking-tight text-white"
                            >
                                {{ formatNumber(target.total_stars ?? 0) }}
                            </div>
                            <div
                                class="mt-1 text-[10px] font-semibold tracking-[0.16em] text-[#6e7681] uppercase"
                            >
                                {{ t('syncTargets.stats.stars') }}
                            </div>
                        </div>

                        <div
                            class="border-l border-white/[0.05] px-6 py-5"
                        >
                            <div
                                class="text-2xl font-semibold tracking-tight text-white"
                            >
                                {{ formatNumber(target.total_issues ?? 0) }}
                            </div>
                            <div
                                class="mt-1 text-[10px] font-semibold tracking-[0.16em] text-[#6e7681] uppercase"
                            >
                                {{ t('syncTargets.stats.issues') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="target.last_error"
                    class="border-b border-[#da3633]/50 bg-[#da3633]/10 px-6 py-3 text-sm text-[#f85149]"
                >
                    {{ t('syncTargets.errors.syncFailed') }}
                </div>

                <!-- Repository toolbar -->
                <div class="border-b border-[#30363d] px-6 py-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-semibold">
                            {{ t('syncTargets.repositories.title') }}
                            <span
                                class="ml-1 text-sm font-normal text-[#8b949e]"
                            >
                                {{ target.repositories.length }}
                            </span>
                        </h3>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input
                            v-model="getFilter(target.id).search"
                            type="search"
                            :placeholder="
                                t('syncTargets.repositories.searchPlaceholder')
                            "
                            :aria-label="t('syncTargets.repositories.search')"
                            @input="searchRepositories(target.id)"
                            class="min-w-0 flex-1 rounded-md border border-[#30363d] bg-[#0d1117] px-3 py-2 text-sm text-[#f0f6fc] transition outline-none placeholder:text-[#6e7681] focus:border-[#58a6ff] focus:ring-1 focus:ring-[#58a6ff]"
                        />

                        <select
                            v-model="getFilter(target.id).language"
                            @change="getFilter(target.id).page = 1"
                            :aria-label="
                                t('syncTargets.repositories.language')
                            "
                            class="rounded-md border border-[#30363d] bg-[#21262d] px-3 py-2 text-sm text-[#f0f6fc] transition outline-none focus:border-[#58a6ff] sm:w-52"
                        >
                            <option value="">
                                {{
                                    t(
                                        'syncTargets.repositories.allLanguages',
                                    )
                                }}
                            </option>

                            <option
                                v-for="item in languages(target)"
                                :key="item"
                                :value="item"
                            >
                                {{ item }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Repositories -->
                <div
                    v-if="target.repositories_count === 0"
                    class="px-6 py-10 text-center text-sm text-[#8b949e]"
                >
                    {{ t('syncTargets.repositories.empty') }}
                </div>

                <div
                    v-else-if="filteredRepositories(target).length > 0"
                    class="divide-y divide-[#30363d]"
                >
                    <article
                        v-for="repository in paginatedRepositories(target)"
                        :key="repository.id"
                        class="flex flex-col gap-4 px-6 py-5 transition hover:bg-[#1c2128] sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    :href="repository.html_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="truncate font-semibold text-[#58a6ff] hover:underline"
                                >
                                    {{ repository.full_name }}
                                </a>

                                <span
                                    v-if="repository.archived"
                                    class="rounded-full border border-[#30363d] px-2 py-0.5 text-xs text-[#8b949e]"
                                >
                                    {{
                                        t(
                                            'syncTargets.repositories.archived',
                                        )
                                    }}
                                </span>
                            </div>

                            <p
                                v-if="repository.description"
                                class="mt-1.5 max-w-3xl text-sm leading-5 text-[#8b949e]"
                            >
                                {{ repository.description }}
                            </p>
                        </div>

                        <div
                            class="flex shrink-0 flex-wrap items-center gap-4 text-xs text-[#8b949e]"
                        >
                            <span
                                v-if="repository.language"
                                class="rounded-md bg-[#21262d] px-2.5 py-1"
                            >
                                {{ repository.language }}
                            </span>

                            <span> ★ {{ repository.stargazers_count }} </span>

                            <span>
                                {{
                                    t(
                                        'syncTargets.repositories.openIssues',
                                    )
                                }}:
                                {{ repository.open_issues_count }}
                            </span>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="px-6 py-10 text-center text-sm text-[#8b949e]"
                >
                    {{ t('syncTargets.repositories.noResults') }}
                </div>

                <!-- Pagination -->
                <div
                    v-if="totalPages(target) > 1"
                    class="flex items-center justify-between border-t border-[#30363d] px-6 py-4"
                >
                    <button
                        type="button"
                        :disabled="getFilter(target.id).page === 1"
                        class="rounded-md border border-[#30363d] px-3 py-1.5 text-sm text-[#c9d1d9] transition hover:bg-[#21262d] disabled:cursor-not-allowed disabled:opacity-40"
                        @click="getFilter(target.id).page--"
                    >
                        {{ t('syncTargets.pagination.previous') }}
                    </button>

                    <span class="text-sm text-[#8b949e]">
                        {{ getFilter(target.id).page }} /
                        {{ totalPages(target) }}
                    </span>

                    <button
                        type="button"
                        :disabled="
                            getFilter(target.id).page === totalPages(target)
                        "
                        class="rounded-md border border-[#30363d] px-3 py-1.5 text-sm text-[#c9d1d9] transition hover:bg-[#21262d] disabled:cursor-not-allowed disabled:opacity-40"
                        @click="getFilter(target.id).page++"
                    >
                        {{ t('syncTargets.pagination.next') }}
                    </button>
                </div>
            </section>
        </main>
    </div>
</template>