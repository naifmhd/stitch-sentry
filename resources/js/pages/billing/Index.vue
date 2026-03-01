<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, CreditCard, Zap } from 'lucide-vue-next';
import SubscribeController from '@/actions/App/Http/Controllers/Billing/SubscribeController';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { billing } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type PaidPlan = {
    slug: string;
    label: string;
    limits: Record<string, unknown>;
    priceConfigured: boolean;
};

type Props = {
    organization: { id: number; name: string } | null;
    currentPlanSlug: string;
    currentPlanLabel: string;
    subscriptionStatus: string | null;
    paidPlans: PaidPlan[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Billing', href: billing() },
];

function subscribe(planSlug: string): void {
    useForm({}).submit(SubscribeController({ planSlug }));
}

function statusBadgeClass(status: string | null): string {
    if (!status) {
        return 'bg-muted text-muted-foreground';
    }

    const active = ['active', 'trialing'].includes(status);

    return active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
}

function formatLimit(key: string, value: unknown): string {
    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (key === 'max_file_size_mb') {
        return `${value} MB`;
    }

    if (key === 'daily_qa_runs') {
        return `${Number(value).toLocaleString()} / day`;
    }

    if (Array.isArray(value)) {
        return value.join(', ');
    }

    return String(value);
}

const humanLabels: Record<string, string> = {
    daily_qa_runs: 'Daily QA Runs',
    max_file_size_mb: 'Max File Size',
    batch_enabled: 'Batch Mode',
    ai_summary: 'AI Summary',
    pdf_export: 'PDF Export',
    presets: 'Presets',
    share_links: 'Share Links',
    team_members: 'Team Members',
    white_label_reports: 'White-Label Reports',
    api_access: 'API Access',
};
</script>

<template>

    <Head title="Billing" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Billing</h1>
                <p v-if="organization" class="text-muted-foreground">
                    {{ organization.name }}
                </p>
            </div>

            <!-- Current Plan -->
            <Card class="max-w-md">
                <CardHeader class="pb-2">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-sm font-medium">
                            Current Plan
                        </CardTitle>
                        <CreditCard class="text-muted-foreground size-4" />
                    </div>
                    <CardDescription>Your active subscription</CardDescription>
                </CardHeader>
                <CardContent class="flex items-center gap-3">
                    <div class="text-2xl font-bold capitalize">
                        {{ currentPlanLabel }}
                    </div>
                    <span v-if="subscriptionStatus" :class="statusBadgeClass(subscriptionStatus)"
                        class="rounded-full px-2 py-0.5 text-xs font-medium capitalize">
                        {{ subscriptionStatus }}
                    </span>
                </CardContent>
            </Card>

            <!-- Available Plans -->
            <div>
                <h2 class="mb-4 text-lg font-semibold">Available Plans</h2>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="plan in paidPlans" :key="plan.slug" :class="plan.slug === currentPlanSlug
                            ? 'border-primary ring-primary ring-2'
                            : ''
                        ">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">
                                    {{ plan.label }}
                                </CardTitle>
                                <CheckCircle2 v-if="plan.slug === currentPlanSlug" class="text-primary size-5" />
                                <Zap v-else class="text-muted-foreground size-5" />
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <!-- Limits -->
                            <dl class="space-y-1 text-sm">
                                <div v-for="(value, key) in plan.limits" :key="key" class="flex justify-between gap-2">
                                    <dt class="text-muted-foreground">
                                        {{
                                            humanLabels[key] ??
                                            String(key).replace(/_/g, ' ')
                                        }}
                                    </dt>
                                    <dd class="text-right font-medium">
                                        {{ formatLimit(String(key), value) }}
                                    </dd>
                                </div>
                            </dl>

                            <!-- Action -->
                            <div>
                                <span v-if="plan.slug === currentPlanSlug" class="text-primary text-sm font-medium">
                                    Current plan
                                </span>
                                <button v-else-if="plan.priceConfigured"
                                    class="bg-primary text-primary-foreground hover:bg-primary/90 w-full rounded-md px-4 py-2 text-sm font-medium transition-colors"
                                    @click="subscribe(plan.slug)">
                                    Upgrade to {{ plan.label }}
                                </button>
                                <span v-else class="text-muted-foreground text-sm">
                                    Contact us to upgrade
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
