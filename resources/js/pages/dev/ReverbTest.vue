<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { useEcho } from '@/composables/useEcho';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    orgId: number;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Reverb Test',
        href: '/dev/reverb-test',
    },
];

interface QaRunProgressPayload {
    type: string;
    ts: string;
    org_id: number;
    actor_id: number | null;
    qa_run_id: number;
    status: string;
    stage: string;
    percent: number;
    message: string;
    meta: Record<string, unknown>;
}

const events = ref<QaRunProgressPayload[]>([]);
const connectionState = ref<'connecting' | 'connected' | 'error'>('connecting');
const sending = ref(false);

let channelUnsubscribe: (() => void) | null = null;

onMounted(() => {
    const echo = useEcho();

    // Track raw WebSocket connection state
    const pusher = echo.connector.pusher;
    pusher.connection.bind('connected', () => { connectionState.value = 'connected'; });
    pusher.connection.bind('disconnected', () => { connectionState.value = 'connecting'; });
    pusher.connection.bind('failed', () => { connectionState.value = 'error'; });
    pusher.connection.bind('unavailable', () => { connectionState.value = 'error'; });

    if (pusher.connection.state === 'connected') {
        connectionState.value = 'connected';
    }

    const channel = echo
        .private(`org.${props.orgId}`)
        .listen('.qa.run.progress', (event: QaRunProgressPayload) => {
            events.value.unshift(event);
        })
        .error(() => {
            connectionState.value = 'error';
        });

    channelUnsubscribe = () => channel.stopListening('.qa.run.progress');
});

onUnmounted(() => {
    channelUnsubscribe?.();
});

async function sendTestEvent(): Promise<void> {
    sending.value = true;

    try {
        const xsrf = decodeURIComponent(
            document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        );

        await fetch('/dev/reverb-test', {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': xsrf,
                Accept: 'application/json',
            },
        });
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Reverb Test" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold">Reverb Test</h1>
                    <p class="text-muted-foreground text-sm">
                        Listening on
                        <code class="bg-muted rounded px-1 py-0.5 text-xs font-mono">
                            private-org.{{ orgId }}
                        </code>
                        for
                        <code class="bg-muted rounded px-1 py-0.5 text-xs font-mono">
                            qa.run.progress
                        </code>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="{
                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': connectionState === 'connected',
                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': connectionState === 'connecting',
                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': connectionState === 'error',
                    }">
                        <span class="size-1.5 rounded-full" :class="{
                            'bg-green-500': connectionState === 'connected',
                            'bg-yellow-500 animate-pulse': connectionState === 'connecting',
                            'bg-red-500': connectionState === 'error',
                        }" />
                        {{
                            connectionState === 'connected' ? 'Connected' :
                                connectionState === 'error' ? 'Error — is Reverb running?' : 'Connecting…' }}
                    </span>

                    <button
                        class="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-3 py-1.5 text-sm font-medium disabled:opacity-50"
                        :disabled="sending" @click="sendTestEvent">
                        {{ sending ? 'Sending…' : 'Send test event' }}
                    </button>
                </div>
            </div>

            <div class="border-border bg-card rounded-lg border">
                <div class="border-border border-b px-4 py-2.5">
                    <span class="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                        Event log
                        <span v-if="events.length" class="ml-1">({{ events.length }})</span>
                    </span>
                </div>

                <div class="max-h-[28rem] overflow-y-auto">
                    <p v-if="events.length === 0" class="text-muted-foreground px-4 py-8 text-center text-sm">
                        Waiting for events…
                    </p>

                    <div v-for="(event, index) in events" :key="index" class="border-border border-b last:border-0">
                        <div class="flex items-start gap-3 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-center gap-2">
                                    <span class="text-xs font-medium">{{ event.type }}</span>
                                    <span class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 text-xs">
                                        {{ event.status }} / {{ event.stage }}
                                    </span>
                                    <span class="text-muted-foreground text-xs">{{ event.percent }}%</span>
                                </div>
                                <p class="text-sm">{{ event.message }}</p>
                                <p class="text-muted-foreground mt-0.5 text-xs">{{ event.ts }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
