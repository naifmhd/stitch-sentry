<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Activity, CreditCard, FileCheck } from 'lucide-vue-next';
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Hero Section -->
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">
                    Welcome to StitchSentry
                </h1>
                <p class="text-muted-foreground max-w-2xl text-lg">
                    Your embroidery quality assurance platform. Upload design
                    files, run automated QA checks, and generate detailed
                    reports — all in one place.
                </p>
            </div>

            <!-- Stats / Placeholder Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium">
                                Recent QA Runs
                            </CardTitle>
                            <FileCheck
                                class="text-muted-foreground size-4"
                            />
                        </div>
                        <CardDescription>
                            Latest quality checks
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">0</div>
                        <p class="text-muted-foreground text-xs">
                            No runs yet — upload a file to get started
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium">
                                Credits / Plan
                            </CardTitle>
                            <CreditCard
                                class="text-muted-foreground size-4"
                            />
                        </div>
                        <CardDescription>
                            Your current usage
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">Free</div>
                        <p class="text-muted-foreground text-xs">
                            Upgrade to unlock AI summaries and batch processing
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium">
                                Activity
                            </CardTitle>
                            <Activity
                                class="text-muted-foreground size-4"
                            />
                        </div>
                        <CardDescription>
                            Recent file uploads
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">—</div>
                        <p class="text-muted-foreground text-xs">
                            Your recent activity will appear here
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty state content area -->
            <Card class="flex flex-1 items-center justify-center">
                <CardContent class="py-12 text-center">
                    <FileCheck
                        class="text-muted-foreground/50 mx-auto mb-4 size-12"
                    />
                    <h3 class="text-lg font-semibold">
                        Ready to check your designs
                    </h3>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Upload an embroidery file to run your first QA check.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
