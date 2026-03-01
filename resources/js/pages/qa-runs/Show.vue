<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEcho } from '@/composables/useEcho';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type QaStage = 'ingest' | 'parse' | 'render' | 'qa' | 'ai' | 'pdf' | 'export';

type QaStatus =
    | 'queued'
    | 'running'
    | 'completed'
    | 'completed_with_failures'
    | 'failed'
    | 'paused';

interface QaRunProp {
    id: number;
    status: QaStatus;
    stage: QaStage | null;
    progress: number;
    preset: string;
    score: number | null;
    risk_level: 'low' | 'medium' | 'high' | null;
    error_code: string | null;
    support_id: string | null;
    started_at: string | null;
    finished_at: string | null;
    design_file: {
        id: number;
        original_name: string;
        ext: string;
    };
}

interface QaRunProgressPayload {
    type: 'qa.run.progress';
    ts: string;
    org_id: number;
    actor_id: number | null;
    qa_run_id: number;
    status: QaStatus;
    stage: QaStage;
    percent: number;
    message: string;
    meta: Record<string, unknown>;
}

interface QaRunFailedPayload {
    type: 'qa.run.failed';
    ts: string;
    org_id: number;
    actor_id: number | null;
    qa_run_id: number;
    status: 'failed';
    support_id: string;
    error_code: string;
    message: string;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    qaRun: QaRunProp;
    findings: unknown[];
    artifacts: unknown[];
    orgId: number;
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'QA Run', href: `/qa-runs/${props.qaRun.id}` },
];

// ─── Reactive state ───────────────────────────────────────────────────────────

const status = ref<QaStatus>(props.qaRun.status);
const stage = ref<QaStage | null>(props.qaRun.stage);
const progress = ref<number>(props.qaRun.progress);
const liveMessage = ref<string>('');
const failedPayload = ref<QaRunFailedPayload | null>(null);

// ─── Stage timeline ───────────────────────────────────────────────────────────

const STAGES: { key: QaStage; label: string }[] = [
    { key: 'ingest', label: 'Ingest' },
    { key: 'parse', label: 'Parse' },
    { key: 'render', label: 'Render' },
    { key: 'qa', label: 'QA' },
    { key: 'ai', label: 'AI' },
    { key: 'pdf', label: 'PDF' },
    { key: 'export', label: 'Export' },
];

const currentStageIndex = computed<number>(() =>
    stage.value ? STAGES.findIndex((s) => s.key === stage.value) : -1,
);

// ─── Status helpers ───────────────────────────────────────────────────────────

const isTerminal = computed<boolean>(() =>
    ['completed', 'completed_with_failures', 'failed'].includes(status.value),
);

const statusVariant = computed<'default' | 'secondary' | 'destructive' | 'outline'>(() => {
    switch (status.value) {
        case 'completed':
            return 'default';
        case 'completed_with_failures':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
});

const statusLabel = computed<string>(() => {
    switch (status.value) {
        case 'queued':
            return 'Queued';
        case 'running':
            return 'Running';
        case 'completed':
            return 'Completed';
        case 'completed_with_failures':
            return 'Completed with issues';
        case 'failed':
            return 'Failed';
        case 'paused':
            return 'Paused';
    }
});

// ─── Realtime subscription ────────────────────────────────────────────────────

let stopListening: (() => void) | null = null;

onMounted(() => {
    if (isTerminal.value) {
        return; // No need to subscribe if already terminal
    }

    const echo = useEcho();
    const channel = echo.private(`org.${props.orgId}`);

    channel.listen('.qa.run.progress', (payload: QaRunProgressPayload) => {
        if (payload.qa_run_id !== props.qaRun.id) {
            return; // Filter by this run only
        }

        status.value = payload.status;
        stage.value = payload.stage;
        progress.value = payload.percent;
        liveMessage.value = payload.message;
    });

    channel.listen('.qa.run.failed', (payload: QaRunFailedPayload) => {
        if (payload.qa_run_id !== props.qaRun.id) {
            return;
        }

        status.value = 'failed';
        failedPayload.value = payload;
        liveMessage.value = payload.message;
    });

    stopListening = () => {
        channel.stopListening('.qa.run.progress');
        channel.stopListening('.qa.run.failed');
    };
});

onUnmounted(() => {
    stopListening?.();
});
</script>

<template>

    <Head :title="`QA Run #${qaRun.id} — ${qaRun.design_file.original_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight">
                        QA Run #{{ qaRun.id }}
                    </h1>
                    <p class="text-muted-foreground text-sm">
                        {{ qaRun.design_file.original_name }}
                        <span class="uppercase">(.{{ qaRun.design_file.ext }})</span>
                    </p>
                </div>
                <Badge :variant="statusVariant" class="self-start sm:self-auto">
                    {{ statusLabel }}
                </Badge>
            </div>

            <!-- Failure alert -->
            <div v-if="status === 'failed'"
                class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">
                    QA run failed
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                    {{ failedPayload?.message ?? 'An unexpected error occurred while processing your file.' }}
                </p>
                <p v-if="qaRun.support_id || failedPayload?.support_id" class="text-muted-foreground mt-2 text-xs">
                    Support ID:
                    <span class="font-mono font-medium">
                        {{ failedPayload?.support_id ?? qaRun.support_id }}
                    </span>
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left column: progress + timeline -->
                <div class="flex flex-col gap-6 lg:col-span-2">
                    <!-- Progress card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">Progress</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">
                                    {{ liveMessage || (status === 'queued' ? 'Waiting in queue…' : 'Processing…') }}
                                </span>
                                <span class="font-semibold tabular-nums">{{ progress }}%</span>
                            </div>
                            <div class="bg-muted h-2 w-full overflow-hidden rounded-full">
                                <div class="bg-primary h-full rounded-full transition-all duration-300"
                                    :style="{ width: `${progress}%` }" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Stage timeline -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">Pipeline stages</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ol class="relative border-l border-gray-200 dark:border-gray-700">
                                <li v-for="(s, index) in STAGES" :key="s.key" class="mb-4 ms-4 last:mb-0">
                                    <div class="absolute -start-1.5 mt-1.5 size-3 rounded-full border" :class="{
                                        'border-green-500 bg-green-500': index < currentStageIndex,
                                        'border-primary bg-primary animate-pulse': index === currentStageIndex,
                                        'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-900':
                                            index > currentStageIndex,
                                    }" />
                                    <p class="text-sm font-medium leading-none" :class="{
                                        'text-green-600 dark:text-green-400': index < currentStageIndex,
                                        'text-foreground': index === currentStageIndex,
                                        'text-muted-foreground': index > currentStageIndex,
                                    }">
                                        {{ s.label }}
                                    </p>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right column: run info + findings placeholder -->
                <div class="flex flex-col gap-6">
                    <!-- Run info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">Run info</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Preset</span>
                                <span class="font-medium capitalize">{{ qaRun.preset }}</span>
                            </div>
                            <div v-if="qaRun.score !== null" class="flex justify-between">
                                <span class="text-muted-foreground">Score</span>
                                <span class="font-semibold">{{ qaRun.score }}</span>
                            </div>
                            <div v-if="qaRun.risk_level" class="flex justify-between">
                                <span class="text-muted-foreground">Risk</span>
                                <span class="font-medium capitalize">{{ qaRun.risk_level }}</span>
                            </div>
                            <div v-if="qaRun.started_at" class="flex justify-between">
                                <span class="text-muted-foreground">Started</span>
                                <span class="text-xs tabular-nums">
                                    {{ new Date(qaRun.started_at).toLocaleString() }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Findings placeholder -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">Findings</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!isTerminal" class="space-y-2">
                                <!-- Skeleton rows while running -->
                                <div v-for="i in 3" :key="i" class="bg-muted h-4 animate-pulse rounded" />
                            </div>
                            <p v-else-if="findings.length === 0" class="text-muted-foreground text-sm">
                                No findings yet.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Artifacts placeholder -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">Artifacts</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!isTerminal" class="grid grid-cols-2 gap-2">
                                <!-- Skeleton image placeholders -->
                                <div v-for="i in 4" :key="i" class="bg-muted aspect-square animate-pulse rounded-lg" />
                            </div>
                            <p v-else-if="artifacts.length === 0" class="text-muted-foreground text-sm">
                                No artifacts yet.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
