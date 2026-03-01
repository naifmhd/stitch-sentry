<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Coins } from 'lucide-vue-next';
import CreditsController from '@/actions/App/Http/Controllers/Billing/CreditsController';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type LedgerEntry = {
    id: number;
    amount: number;
    reason: string;
    meta_json: Record<string, unknown> | null;
    created_at: string;
};

type OrganizationData = {
    id: number;
    name: string;
};

type Props = {
    organization: OrganizationData;
    balance: number;
    entries: LedgerEntry[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Credits', href: CreditsController(props.organization.id) },
];

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Credits" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Credits</h1>
                    <p class="text-muted-foreground">{{ organization.name }}</p>
                </div>
            </div>

            <!-- Balance Card -->
            <Card class="max-w-xs">
                <CardHeader class="pb-2">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-sm font-medium">Available Balance</CardTitle>
                        <Coins class="text-muted-foreground size-4" />
                    </div>
                    <CardDescription>Current organization credits</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{{ balance.toLocaleString() }}</div>
                </CardContent>
            </Card>

            <!-- Ledger Entries -->
            <Card>
                <CardHeader>
                    <CardTitle>Transaction History</CardTitle>
                    <CardDescription>All credit and debit entries for this organization.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="divide-y">
                        <div
                            v-for="entry in entries"
                            :key="entry.id"
                            class="flex items-center justify-between py-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    :class="entry.amount > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                    class="flex size-8 items-center justify-center rounded-full"
                                >
                                    <ArrowUp v-if="entry.amount > 0" class="size-4" />
                                    <ArrowDown v-else class="size-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">{{ entry.reason }}</p>
                                    <p class="text-muted-foreground text-xs">{{ formatDate(entry.created_at) }}</p>
                                </div>
                            </div>
                            <span
                                :class="entry.amount > 0 ? 'text-green-700' : 'text-red-700'"
                                class="text-sm font-semibold tabular-nums"
                            >
                                {{ entry.amount > 0 ? '+' : '' }}{{ entry.amount.toLocaleString() }}
                            </span>
                        </div>

                        <div v-if="entries.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            No transactions yet.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
